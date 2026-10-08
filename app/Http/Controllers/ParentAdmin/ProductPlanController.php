<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentAdmin\BulkStoreProductPlansRequest;
use App\Http\Requests\ParentAdmin\BulkUpdateProductPlansRequest;
use App\Http\Requests\ParentAdmin\SaveProductPlanConfigurationRequest;
use App\Http\Requests\ParentAdmin\StoreProductPlanRequest;
use App\Http\Requests\ParentAdmin\UpdateProductPlanRequest;
use App\Models\Network;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanParentPrice;
use App\Models\ProductPlanCategory;
use App\Services\ParentAdmin\ParentCatalogService;
use App\Services\ParentAdmin\ProductPlanRouteSwitchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductPlanController extends Controller
{
    public function __construct(
        private readonly ParentCatalogService $catalog,
        private readonly ProductPlanRouteSwitchService $routeSwitcher,
    ) {}

    public function index(Request $request): View
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        $requestedPageSize = (string) $request->input('per_page', '50');
        $allowedPageSizes = ['50', '100', '200', '500', '1000', '2000', 'all'];
        $pageSize = in_array($requestedPageSize, $allowedPageSizes, true) ? $requestedPageSize : '50';
        $perPage = $pageSize === 'all'
            ? max(1, ProductPlan::query()->where('parent_business_id', $parent->id)->count())
            : (int) $pageSize;

        return view('parent-admin.product-plans.index', [
            'plans' => $this->catalog->plans($parent, $perPage, $request->only(['search', 'category_id'])),
            'pageSize' => $pageSize,
            'pageSizes' => $allowedPageSizes,
            'categories' => ProductPlanCategory::query()
                ->with(['product:id,product_name', 'network:id,network_name'])
                ->orderBy('product_plan_category_name')->get(),
            'products' => Product::query()->orderBy('product_name')->get(['id', 'product_name']),
            'networks' => Network::query()->orderBy('network_name')->get(['id', 'network_name']),
            'levels' => $parent->resellerLevels()->where('status', 'active')->orderBy('position')->get(['id', 'name', 'position']),
            'connections' => $parent->providerConnections()
                ->where('status', 'active')->where('approval_status', 'approved')
                ->whereHas('providerConnection', fn ($query) => $query->where('status', 'active'))
                ->with('providerConnection:id,name,slug')->orderBy('name')
                ->get(['id', 'provider_connection_id', 'name']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;

        return response()->json([
            'categories' => ProductPlanCategory::query()
                ->with(['product:id,product_name', 'network:id,network_name'])
                ->orderBy('product_plan_category_name')
                ->get(),
            'plans' => $this->catalog->plans($parent),
            'levels' => $parent->resellerLevels()->where('status', 'active')->orderBy('position')->get(['id', 'name', 'position']),
            'connections' => $parent->providerConnections()
                ->where('status', 'active')
                ->where('approval_status', 'approved')
                ->whereHas('providerConnection', fn ($query) => $query->where('status', 'active'))
                ->with('providerConnection:id,name,slug')
                ->orderBy('name')
                ->get(['id', 'provider_connection_id', 'name']),
        ]);
    }

    public function edit(Request $request, ProductPlan $plan): View
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        abort_unless($plan->parent_business_id === $parent->id, 404);

        return view('parent-admin.product-plans.edit', [
            'plan' => $plan->load(['providerRoutes', 'parentPrices']),
            'categories' => ProductPlanCategory::query()->with(['product:id,product_name', 'network:id,network_name'])->orderBy('product_plan_category_name')->get(),
            'levels' => $parent->resellerLevels()->where('status', 'active')->orderBy('position')->get(['id', 'name', 'position']),
            'connections' => $parent->providerConnections()->where('status', 'active')->where('approval_status', 'approved')
                ->whereHas('providerConnection', fn ($query) => $query->where('status', 'active'))
                ->with('providerConnection:id,name')->orderBy('name')->get(['id', 'provider_connection_id', 'name']),
        ]);
    }

    public function updateConfiguration(SaveProductPlanConfigurationRequest $request, ProductPlan $plan): JsonResponse|RedirectResponse
    {
        $plan = $this->catalog->updateConfiguration(
            $request->user('parent_admin')->parentBusiness,
            $plan,
            $request->validated(),
            $this->routeSwitcher,
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Product plan configuration updated.', 'plan' => $plan]);
        }

        return redirect()->route('parent-admin.product-plans.index')->with('success', 'Product plan configuration updated.');
    }

    public function store(StoreProductPlanRequest $request): JsonResponse|RedirectResponse
    {
        $plan = $this->catalog->createPlan(
            $request->user('parent_admin')->parentBusiness,
            $request->validated(),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Product plan added.',
                'plan' => $plan,
            ], 201);
        }

        return redirect()->route('parent-admin.product-plans.index')->with('success', 'Product plan added.');
    }

    public function bulkStore(BulkStoreProductPlansRequest $request): JsonResponse|RedirectResponse
    {
        $plans = $this->catalog->createPlans(
            $request->user('parent_admin')->parentBusiness,
            $request->validated('plans'),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "{$plans->count()} product plans added.",
                'created_count' => $plans->count(),
                'plans' => $plans,
            ], 201);
        }

        return redirect()->route('parent-admin.product-plans.index')
            ->with('success', "{$plans->count()} product plans added.");
    }

    public function bulkUpdate(BulkUpdateProductPlansRequest $request): JsonResponse|RedirectResponse
    {
        $plans = $request->selectedPlans();
        $action = $request->validated('action');

        DB::transaction(function () use ($plans, $action): void {
            foreach ($plans as $plan) {
                $attributes = match ($action) {
                    'activate' => ['visibility' => true],
                    'deactivate' => ['visibility' => false, 'affiliate_visibility' => false, 'public_visibility' => false],
                    'show_affiliates' => ['affiliate_visibility' => true],
                    'hide_affiliates' => ['affiliate_visibility' => false],
                    'show_public' => ['public_visibility' => true],
                    'hide_public' => ['public_visibility' => false],
                };
                $plan->update($attributes);
                if (array_key_exists('visibility', $attributes)) {
                    $plan->providerRoutes()->where('priority', 1)->update(['active' => $attributes['visibility']]);
                }
            }
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => "{$plans->count()} product plans updated.", 'updated_count' => $plans->count()]);
        }

        return redirect()->route('parent-admin.product-plans.index', array_filter($request->only(['search', 'category_id'])))
            ->with('success', "{$plans->count()} product plans updated.");
    }

    public function bulkUpdateConfigurations(Request $request): JsonResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        $data = $request->validate([
            'plans' => ['required', 'array', 'min:1', 'max:30'],
            'plans.*.id' => ['required', 'integer', 'distinct'],
            'plans.*.product_plan_name' => ['required', 'string', 'max:255'],
            'plans.*.product_plan_category_id' => ['required', 'integer', Rule::exists('product_plan_categories', 'id')],
            'plans.*.api_id' => ['nullable', 'string', 'max:255'],
            'plans.*.admin_cost_price' => ['nullable', 'numeric', 'min:0'],
            'plans.*.cost_price' => ['required', 'numeric', 'min:0'],
            'plans.*.data_size_in_mb' => ['nullable', 'numeric', 'min:0'],
            'plans.*.validity_in_days' => ['nullable', 'integer', 'min:0'],
            'plans.*.profit_category' => ['required', Rule::in(['flat', 'percent'])],
            'plans.*.visibility' => ['required', 'boolean'],
            'plans.*.affiliate_visibility' => ['required', 'boolean'],
            'plans.*.public_visibility' => ['required', 'boolean'],
            'plans.*.route' => ['nullable', 'array'],
            'plans.*.route.parent_provider_connection_id' => ['nullable', 'integer'],
            'plans.*.route.provider_plan_id' => ['nullable', 'string', 'max:255'],
            'plans.*.prices' => ['nullable', 'array', 'max:6'],
            'plans.*.prices.*.parent_reseller_level_id' => ['required', 'integer'],
            'plans.*.prices.*.selling_price' => ['nullable', 'numeric', 'min:0'],
            'plans.*.prices.*.max_profit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $submitted = collect($data['plans']);
        $plans = ProductPlan::query()
            ->where('parent_business_id', $parent->id)
            ->whereIn('id', $submitted->pluck('id'))
            ->get()
            ->keyBy('id');

        if ($plans->count() !== $submitted->count()) {
            throw ValidationException::withMessages(['plans' => 'One or more selected plans do not belong to this parent.']);
        }

        DB::transaction(function () use ($parent, $submitted, $plans): void {
            foreach ($submitted as $row) {
                $plan = $plans->get((int) $row['id']);
                unset($row['id']);

                $this->catalog->updateConfiguration(
                    $parent,
                    $plan,
                    $row,
                    $this->routeSwitcher,
                );
            }
        });

        return response()->json([
            'message' => $submitted->count().' product plan configurations updated.',
            'updated_count' => $submitted->count(),
        ]);
    }

    public function pastePricePreview(Request $request): View|RedirectResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        $data = $request->validate([
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'network_id' => ['nullable', 'integer', Rule::exists('networks', 'id')],
            'product_plan_category_id' => ['required', 'integer', Rule::exists('product_plan_categories', 'id')],
            'raw_text' => ['required', 'string', 'min:3'],
            'selling_margin' => ['required', 'numeric', 'min:0.01'],
            'affiliate_visibility' => ['required', 'boolean'],
            'public_visibility' => ['required', 'boolean'],
        ]);

        $category = ProductPlanCategory::query()->findOrFail($data['product_plan_category_id']);
        if (filled($data['product_id'] ?? null) && (int) $category->product_id !== (int) $data['product_id']) {
            throw ValidationException::withMessages(['product_plan_category_id' => 'Selected category does not belong to the selected product.']);
        }
        if (filled($data['network_id'] ?? null) && (int) $category->network_id !== (int) $data['network_id']) {
            throw ValidationException::withMessages(['product_plan_category_id' => 'Selected category does not belong to the selected network.']);
        }

        $levels = $parent->resellerLevels()->where('status', 'active')->orderBy('position')->get(['id']);
        if ($levels->isEmpty()) {
            throw ValidationException::withMessages(['raw_text' => 'Create at least one active reseller level before updating prices.']);
        }

        $parsed = $this->parsePastedPriceRows($data['raw_text'], (float) $data['selling_margin']);
        if ($parsed['rows'] === []) {
            throw ValidationException::withMessages(['raw_text' => 'No valid rows were found. Paste columns like: Plan name | api_id | cost price | selling price.']);
        }

        $seen = [];
        $rows = [];
        $errors = $parsed['errors'];
        foreach ($parsed['rows'] as $row) {
            $key = strtolower($row['api_id']);
            if (isset($seen[$key])) {
                $errors[] = "Line {$row['line']}: api_id {$row['api_id']} already appears on line {$seen[$key]}.";
                continue;
            }
            $seen[$key] = $row['line'];
            $existing = ProductPlan::query()
                ->where('parent_business_id', $parent->id)
                ->where('product_plan_category_id', $data['product_plan_category_id'])
                ->where('api_id', $row['api_id'])
                ->first(['id', 'product_plan_name', 'cost_price']);
            $rows[] = [...$row, 'classification' => $existing ? 'update' : 'create', 'existing' => $existing?->toArray()];
        }

        $token = null;
        if ($errors === []) {
            $token = (string) Str::uuid();
            $request->session()->put("paste_price_update.{$token}", [
                'parent_id' => $parent->id,
                'category_id' => (int) $data['product_plan_category_id'],
                'affiliate_visibility' => (bool) $data['affiliate_visibility'],
                'public_visibility' => (bool) $data['public_visibility'],
                'rows' => $rows,
                'expires' => now()->addMinutes(30)->timestamp,
            ]);
        }

        return view('parent-admin.product-plans.paste-preview', [
            'rows' => $rows,
            'parseErrors' => $errors,
            'validationErrors' => [],
            'token' => $token,
            'category' => $category->load(['product:id,product_name', 'network:id,network_name']),
        ]);
    }

    public function pastePriceConfirm(Request $request): View|RedirectResponse
    {
        $token = (string) $request->input('token');
        $parent = $request->user('parent_admin')->parentBusiness;
        $sessionKey = 'paste_price_update.'.$token;
        $payload = $request->session()->get($sessionKey);
        abort_unless($payload && (int) $payload['parent_id'] === (int) $parent->id && $payload['expires'] >= time(), 410, 'Paste preview expired.');

        $validator = Validator::make($request->all(), [
            'token' => ['required', 'uuid'],
            'rows' => ['required', 'array', 'min:1', 'max:200'],
            'rows.*.product_plan_name' => ['required', 'string', 'max:255'],
            'rows.*.api_id' => ['required', 'string', 'max:255', 'distinct:strict'],
            'rows.*.cost_price' => ['required', 'numeric', 'min:0'],
            'rows.*.selling_price' => ['required', 'numeric', 'gt:rows.*.cost_price'],
        ], [
            'rows.*.api_id.distinct' => 'Each API ID must appear only once.',
            'rows.*.selling_price.gt' => 'Each selling price must be greater than its cost price.',
        ]);

        if ($validator->fails()) {
            $submittedRows = $request->input('rows', []);
            $rows = collect($payload['rows'])->map(function (array $row, int $index) use ($submittedRows): array {
                return [...$row, ...($submittedRows[$index] ?? [])];
            })->all();
            $category = ProductPlanCategory::query()->with(['product:id,product_name', 'network:id,network_name'])->findOrFail($payload['category_id']);

            return view('parent-admin.product-plans.paste-preview', [
                'rows' => $rows,
                'parseErrors' => [],
                'validationErrors' => $validator->errors()->all(),
                'token' => $token,
                'category' => $category,
            ]);
        }

        $data = $validator->validated();

        $payload['rows'] = collect($data['rows'])->map(function (array $row): array {
            $cost = round((float) $row['cost_price'], 2);
            $selling = round((float) $row['selling_price'], 2);

            return [
                'product_plan_name' => trim($row['product_plan_name']),
                'api_id' => trim($row['api_id']),
                'cost_price' => number_format($cost, 2, '.', ''),
                'selling_price' => number_format($selling, 2, '.', ''),
                'margin' => number_format($selling - $cost, 2, '.', ''),
            ];
        })->all();

        $levels = $parent->resellerLevels()->where('status', 'active')->orderBy('position')->get(['id']);
        $counts = DB::transaction(function () use ($parent, $payload, $levels): array {
            $counts = ['created' => 0, 'updated' => 0];
            foreach ($payload['rows'] as $row) {
                $plan = ProductPlan::query()
                    ->where('parent_business_id', $parent->id)
                    ->where('product_plan_category_id', $payload['category_id'])
                    ->where('api_id', $row['api_id'])
                    ->first();

                $attributes = [
                    'product_plan_name' => $row['product_plan_name'],
                    'product_plan_category_id' => $payload['category_id'],
                    'api_id' => $row['api_id'],
                    'admin_cost_price' => $row['cost_price'],
                    'cost_price' => $row['cost_price'],
                    'profit_category' => 'flat',
                    'visibility' => true,
                    'affiliate_visibility' => (bool) $payload['affiliate_visibility'],
                    'public_visibility' => (bool) $payload['public_visibility'],
                ];

                if ($plan) {
                    $plan->update($attributes);
                    $plan->providerRoutes()->where('priority', 1)->update(['active' => true]);
                    $counts['updated']++;
                } else {
                    $plan = $parent->productPlans()->create($attributes);
                    $counts['created']++;
                }

                foreach ($levels as $level) {
                    ProductPlanParentPrice::query()->updateOrCreate(
                        [
                            'product_plan_id' => $plan->id,
                            'parent_reseller_level_id' => $level->id,
                        ],
                        [
                            'parent_business_id' => $parent->id,
                            'selling_price' => $row['selling_price'],
                            'max_profit' => $row['margin'],
                        ],
                    );
                }
            }

            return $counts;
        });

        $request->session()->forget($sessionKey);

        return redirect()->route('parent-admin.product-plans.index')
            ->with('success', "Paste update complete: {$counts['created']} created, {$counts['updated']} updated.");
    }

    public function disable(Request $request, ProductPlan $plan): RedirectResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        abort_unless((int) $plan->parent_business_id === (int) $parent->id, 404);

        DB::transaction(function () use ($plan): void {
            $plan->update(['visibility' => false, 'affiliate_visibility' => false, 'public_visibility' => false]);
            $plan->providerRoutes()->where('priority', 1)->update(['active' => false]);
        });

        return redirect()->route('parent-admin.dashboard')->with('success', 'Plan disabled across all affiliates. Affiliate prices and local settings were preserved.');
    }

    public function update(UpdateProductPlanRequest $request, ProductPlan $plan): JsonResponse|RedirectResponse
    {
        $plan = $this->catalog->updatePlan(
            $request->user('parent_admin')->parentBusiness,
            $plan,
            $request->validated(),
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Product plan updated.', 'plan' => $plan]);
        }

        return redirect()->route('parent-admin.product-plans.index')->with('success', 'Product plan updated.');
    }

    private function parsePastedPriceRows(string $rawText, float $defaultMargin): array
    {
        if ($this->looksLikeCopiedPlanTable($rawText)) {
            return $this->parseCopiedPlanTable($rawText, $defaultMargin);
        }

        $lines = preg_split('/\R/', trim($rawText)) ?: [];
        $rows = [];
        $errors = [];
        $headers = null;

        foreach ($lines as $offset => $line) {
            $line = trim($line);
            $lineNumber = $offset + 1;
            if ($line === '') {
                continue;
            }

            $columns = $this->splitPastedPriceLine($line);
            if ($headers === null && $this->looksLikeHeader($columns)) {
                $headers = $this->normalizePastedHeaders($columns);
                continue;
            }

            $record = $headers ? $this->recordFromHeaders($headers, $columns) : $this->recordFromColumns($columns, $line);
            if (! $record) {
                $errors[] = "Line {$lineNumber}: could not read plan name, api_id and cost price.";
                continue;
            }

            $apiId = trim((string) ($record['api_id'] ?? ''));
            $planName = trim((string) ($record['product_plan_name'] ?? ''));
            $cost = $this->moneyValue($record['cost_price'] ?? null);
            $selling = $this->moneyValue($record['selling_price'] ?? null);

            if ($planName === '' || $apiId === '' || $cost === null) {
                $errors[] = "Line {$lineNumber}: plan name, api_id and cost price are required.";
                continue;
            }

            $selling = round(($selling ?? $cost) + $defaultMargin, 2);
            if ($selling <= $cost) {
                $errors[] = "Line {$lineNumber}: selling price must be greater than cost price.";
                continue;
            }

            $rows[] = [
                'line' => $lineNumber,
                'product_plan_name' => $planName,
                'api_id' => $apiId,
                'cost_price' => number_format($cost, 2, '.', ''),
                'selling_price' => number_format($selling, 2, '.', ''),
                'margin' => number_format($selling - $cost, 2, '.', ''),
            ];
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    private function looksLikeCopiedPlanTable(string $rawText): bool
    {
        return str_contains($rawText, 'Best provider')
            && str_contains($rawText, 'API ID')
            && preg_match('/^\s*\d+\s*$/m', $rawText);
    }

    private function parseCopiedPlanTable(string $rawText, float $defaultMargin): array
    {
        $lines = collect(preg_split('/\R/', trim($rawText)) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter(fn ($line) => $line !== '' && ! str_starts_with($line, '#'))
            ->values()
            ->all();
        $rows = [];
        $errors = [];
        $count = count($lines);

        for ($index = 0; $index < $count; $index++) {
            if (! $this->isCopiedTableRecordStart($lines, $index)) {
                continue;
            }

            $lineNumber = $index + 1;
            $nextRecord = $count;
            for ($cursor = $index + 1; $cursor < $count; $cursor++) {
                if ($this->isCopiedTableRecordStart($lines, $cursor)) {
                    $nextRecord = $cursor;
                    break;
                }
            }

            $segment = array_slice($lines, $index + 1, $nextRecord - $index - 1);
            $planName = $segment[0] ?? '';
            $apiId = null;
            foreach ($segment as $segmentIndex => $value) {
                if (strtoupper($value) === 'ON' && isset($segment[$segmentIndex + 1])) {
                    $apiId = trim((string) $segment[$segmentIndex + 1]);
                    break;
                }
            }

            $moneyValues = [];
            foreach ($segment as $value) {
                if (preg_match_all('/₦\s*([0-9][0-9,]*(?:\.[0-9]+)?)/u', $value, $matches)) {
                    foreach ($matches[1] as $amount) {
                        $moneyValues[] = $this->moneyValue($amount);
                    }
                }
            }

            $cost = $moneyValues[count($moneyValues) - 2] ?? null;
            $baseSelling = $moneyValues[count($moneyValues) - 1] ?? null;

            if ($planName === '' || $apiId === null || $apiId === '' || $cost === null || $baseSelling === null) {
                $errors[] = "Line {$lineNumber}: could not read plan name, api_id, cost and selling price from copied table row.";
                $index = $nextRecord - 1;
                continue;
            }

            $selling = round($baseSelling + $defaultMargin, 2);
            if ($selling <= $cost) {
                $errors[] = "Line {$lineNumber}: selling price must be greater than cost price.";
                $index = $nextRecord - 1;
                continue;
            }

            $rows[] = [
                'line' => $lineNumber,
                'product_plan_name' => $planName,
                'api_id' => $apiId,
                'cost_price' => number_format($cost, 2, '.', ''),
                'selling_price' => number_format($selling, 2, '.', ''),
                'margin' => number_format($selling - $cost, 2, '.', ''),
            ];

            $index = $nextRecord - 1;
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    private function isCopiedTableRecordStart(array $lines, int $index): bool
    {
        if (! isset($lines[$index], $lines[$index + 1]) || ! preg_match('/^\d+$/', $lines[$index])) {
            return false;
        }

        $next = $lines[$index + 1];
        if (! preg_match('/[A-Za-z]/', $next)) {
            return false;
        }
        if (str_contains($next, "\t") || str_contains($next, '₦')) {
            return false;
        }

        $blocked = ['providers', 'provider', 'oresamplug', 'gongozconcept', 'affatech', 'paultechs', 'd', 'c', 'on', 'data'];
        if (in_array(strtolower($next), $blocked, true)) {
            return false;
        }

        $lookahead = array_slice($lines, $index + 1, 5);
        return collect($lookahead)->contains(fn ($line) => str_starts_with(strtolower($line), 'type:'));
    }

    private function splitPastedPriceLine(string $line): array
    {
        if (str_contains($line, "\t")) {
            return array_map('trim', explode("\t", $line));
        }

        foreach (['|', ';', ','] as $delimiter) {
            if (str_contains($line, $delimiter)) {
                return array_map('trim', str_getcsv($line, $delimiter, '"', '\\'));
            }
        }

        return array_map('trim', preg_split('/\s{2,}/', $line) ?: []);
    }

    private function looksLikeHeader(array $columns): bool
    {
        $joined = strtolower(implode(' ', $columns));
        return str_contains($joined, 'api') && (str_contains($joined, 'cost') || str_contains($joined, 'price'));
    }

    private function normalizePastedHeaders(array $columns): array
    {
        return collect($columns)->mapWithKeys(function ($header, $index) {
            $header = strtolower(trim((string) $header));
            $header = preg_replace('/[^a-z0-9]+/', '_', $header);
            $field = match (true) {
                in_array($header, ['plan', 'name', 'plan_name', 'product_plan', 'product_plan_name'], true) => 'product_plan_name',
                in_array($header, ['api', 'api_id', 'apiid', 'plan_id', 'provider_id'], true) => 'api_id',
                in_array($header, ['cost', 'cost_price', 'admin_cost', 'admin_cost_price'], true) => 'cost_price',
                in_array($header, ['price', 'selling_price', 'sell_price', 'reseller_price'], true) => 'selling_price',
                default => $header,
            };

            return [$field => $index];
        })->all();
    }

    private function recordFromHeaders(array $headers, array $columns): array
    {
        return [
            'product_plan_name' => $columns[$headers['product_plan_name'] ?? -1] ?? null,
            'api_id' => $columns[$headers['api_id'] ?? -1] ?? null,
            'cost_price' => $columns[$headers['cost_price'] ?? -1] ?? null,
            'selling_price' => $columns[$headers['selling_price'] ?? -1] ?? null,
        ];
    }

    private function recordFromColumns(array $columns, string $line): ?array
    {
        if (count($columns) >= 3) {
            return [
                'product_plan_name' => $columns[0],
                'api_id' => $columns[1],
                'cost_price' => $columns[2],
                'selling_price' => $columns[3] ?? null,
            ];
        }

        if (preg_match('/^(?<name>.+?)\s+(?<api>[A-Za-z0-9._:-]+)\s+(?<cost>[0-9][0-9,]*(?:\.[0-9]+)?)(?:\s+(?<selling>[0-9][0-9,]*(?:\.[0-9]+)?))?$/', $line, $matches)) {
            return [
                'product_plan_name' => $matches['name'],
                'api_id' => $matches['api'],
                'cost_price' => $matches['cost'],
                'selling_price' => $matches['selling'] ?? null,
            ];
        }

        return null;
    }

    private function moneyValue(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9.]/', '', (string) $value);
        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
