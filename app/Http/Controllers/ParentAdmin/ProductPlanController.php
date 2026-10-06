<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentAdmin\BulkStoreProductPlansRequest;
use App\Http\Requests\ParentAdmin\BulkUpdateProductPlansRequest;
use App\Http\Requests\ParentAdmin\SaveProductPlanConfigurationRequest;
use App\Http\Requests\ParentAdmin\StoreProductPlanRequest;
use App\Http\Requests\ParentAdmin\UpdateProductPlanRequest;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Services\ParentAdmin\ParentCatalogService;
use App\Services\ParentAdmin\ProductPlanRouteSwitchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
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
            'plans' => ['required', 'array', 'min:1', 'max:15'],
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
}
