<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Models\ParentBusiness;
use App\Models\ProductPlan;
use App\Services\ParentAdmin\ParentCatalogService;
use App\Services\ParentAdmin\ProductPlanWorkbookService;
use App\Services\ParentAdmin\ProductPlanRouteSwitchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductPlanImportController extends Controller
{
    public function __construct(private readonly ProductPlanWorkbookService $workbooks) {}

    public function template(Request $request)
    {
        $parent = $request->user('parent_admin')->parentBusiness;

        return response()->streamDownload(
            fn () => $this->workbooks->write($this->workbooks->workbook($parent), 'php://output'),
            $parent->slug.'-product-plans.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function preview(Request $request)
    {
        $request->validate([
            'plans_file' => ['required_without:plans_csv', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
            'plans_csv' => ['required_without:plans_file', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);
        $parent = $request->user('parent_admin')->parentBusiness;
        $file = $request->file('plans_file') ?? $request->file('plans_csv');
        $parsed = $this->workbooks->rows($file, $parent);
        [$categories, $connections] = $this->workbooks->lookupMaps($parent);
        $levels = $parent->resellerLevels()->where('status', 'active')->orderBy('position')->get();
        if ($levels->count() !== 6) {
            throw ValidationException::withMessages(['plans_file' => 'Create exactly six active reseller levels before importing plans.']);
        }

        $rows = [];
        $errors = [];
        $seenProviderPlans = [];
        foreach ($parsed as $parsedRow) {
            try {
                $normalized = $this->normalizeRow($parent, $parsedRow['values'], $parsedRow['line'], $categories, $connections, $levels);
                $route = $normalized['attributes']['route'];
                $uniqueKey = $route['parent_provider_connection_id'].'|'.strtolower($route['provider_plan_id']);
                if (isset($seenProviderPlans[$uniqueKey])) {
                    throw ValidationException::withMessages([
                        'row' => "Row {$parsedRow['line']}: this connection and Provider Plan ID already appear on row {$seenProviderPlans[$uniqueKey]}.",
                    ]);
                }
                $seenProviderPlans[$uniqueKey] = $parsedRow['line'];
                $rows[] = $normalized;
            } catch (ValidationException $exception) {
                $errors[$parsedRow['line']] = collect($exception->errors())->flatten()->implode(' ');
            }
        }
        if ($rows === [] && $errors === []) {
            throw ValidationException::withMessages(['plans_file' => 'No completed product-plan rows were found.']);
        }

        $token = null;
        if ($errors === []) {
            $token = (string) Str::uuid();
            $request->session()->put("plan_import.{$token}", [
                'parent' => $parent->id,
                'rows' => $rows,
                'expires' => now()->addMinutes(30)->timestamp,
            ]);
        }

        return view('parent-admin.product-plans.import-preview', compact('rows', 'errors', 'token'));
    }

    public function confirm(Request $request, ParentCatalogService $catalog, ProductPlanRouteSwitchService $routeSwitcher)
    {
        $request->validate(['token' => ['required', 'uuid']]);
        $payload = $request->session()->pull('plan_import.'.$request->token);
        $parent = $request->user('parent_admin')->parentBusiness;
        abort_unless($payload && (int) $payload['parent'] === (int) $parent->id && $payload['expires'] >= time(), 410, 'Import preview expired.');

        $counts = DB::transaction(function () use ($payload, $parent, $catalog, $routeSwitcher) {
            $counts = ['new' => 0, 'update' => 0];
            foreach ($payload['rows'] as $row) {
                $attributes = $row['attributes'];
                if ($row['classification'] === 'update') {
                    $plan = ProductPlan::query()->where('parent_business_id', $parent->id)->findOrFail($row['existing_plan_id']);
                    $catalog->updateConfiguration($parent, $plan, $attributes, $routeSwitcher);
                    $counts['update']++;
                } else {
                    $catalog->createPlan($parent, $attributes);
                    $counts['new']++;
                }
            }

            return $counts;
        });

        return redirect()->route('parent-admin.product-plans.index')
            ->with('success', "Import complete: {$counts['new']} created, {$counts['update']} updated.");
    }

    private function normalizeRow(ParentBusiness $parent, array $row, int $line, $categories, $connections, $levels): array
    {
        $category = $categories->get(trim((string) ($row['Category'] ?? '')));
        $connection = $connections->get(trim((string) ($row['Provider Connection'] ?? '')));
        $providerPlanId = trim((string) ($row['Provider Plan ID'] ?? ''));
        $providerCost = $row['Provider Cost'] ?? null;
        $errors = [];
        if (! $category) {
            $errors[] = "Row {$line}: choose a valid category from the template dropdown.";
        }
        if (! $connection) {
            $errors[] = "Row {$line}: choose an approved connection owned by this parent.";
        }
        if ($providerPlanId === '') {
            $errors[] = "Row {$line}: Provider Plan ID is required.";
        }
        if (! is_numeric($providerCost) || (float) $providerCost < 0) {
            $errors[] = "Row {$line}: Provider Cost must be a valid non-negative amount.";
        }

        $prices = [];
        foreach ($levels->values() as $index => $level) {
            $price = $row["{$level->name} Price"] ?? null;
            $max = $row["{$level->name} Max Profit"] ?? null;
            if (! is_numeric($price)) {
                $errors[] = "Row {$line}: {$level->name} price is required.";
            } elseif (is_numeric($providerCost) && (float) $price <= (float) $providerCost) {
                $errors[] = "Row {$line}: {$level->name} price must be greater than provider cost.";
            }
            if (filled($max) && (! is_numeric($max) || (float) $max < 0)) {
                $errors[] = "Row {$line}: {$level->name} maximum profit must be a non-negative amount.";
            }
            $prices[] = [
                'parent_reseller_level_id' => $level->id,
                'selling_price' => $price,
                'max_profit' => filled($max) ? $max : null,
            ];
        }
        if ($errors) {
            throw ValidationException::withMessages(['row' => $errors]);
        }

        $existing = ProductPlan::query()
            ->where('parent_business_id', $parent->id)
            ->whereHas('providerRoutes', fn ($query) => $query
                ->where('parent_provider_connection_id', $connection->id)
                ->where('provider_plan_id', $providerPlanId))
            ->first();
        $pricing = strtolower(trim((string) ($row['Pricing Mode'] ?? 'flat')));
        $attributes = [
            'product_plan_name' => trim((string) $row['Plan Name']),
            'product_plan_category_id' => $category->id,
            'api_id' => filled($row['Internal Reference'] ?? null) ? trim((string) $row['Internal Reference']) : null,
            'admin_cost_price' => $providerCost,
            'cost_price' => $providerCost,
            'data_size_in_mb' => is_numeric($row['Data Size MB'] ?? null) ? $row['Data Size MB'] : null,
            'validity_in_days' => is_numeric($row['Validity Days'] ?? null) ? $row['Validity Days'] : null,
            'profit_category' => str_starts_with($pricing, 'percent') ? 'percent' : 'flat',
            'commission_feature' => false,
            'upline_commission_option' => 'flat',
            'upline_percentage_commission' => 0,
            'upline_flat_commission' => 0,
            'upline_commission_cap' => 0,
            'visibility' => $this->boolean($row['Active'] ?? false),
            'affiliate_visibility' => $this->boolean($row['Affiliate Visible'] ?? false),
            'public_visibility' => $this->boolean($row['Public Visible'] ?? false),
            'route' => [
                'parent_provider_connection_id' => $connection->id,
                'provider_plan_id' => $providerPlanId,
            ],
            'prices' => $prices,
        ];

        return [
            'line' => $line,
            'classification' => $existing ? 'update' : 'new',
            'existing_plan_id' => $existing?->id,
            'category_label' => $this->workbooks->categoryLabel($category),
            'connection_label' => $this->workbooks->connectionLabel($connection),
            'attributes' => $attributes,
        ];
    }

    private function boolean(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'yes', 'true', 'active'], true);
    }
}
