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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ProductPlanController extends Controller
{
    public function __construct(private readonly ParentCatalogService $catalog) {}

    public function index(Request $request): View
    {
        $parent = $request->user('parent_admin')->parentBusiness;

        return view('parent-admin.product-plans.index', [
            'plans' => $this->catalog->plans($parent, 25, $request->only(['search', 'category_id'])),
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

    public function updateConfiguration(SaveProductPlanConfigurationRequest $request, ProductPlan $plan): RedirectResponse
    {
        $this->catalog->updateConfiguration($request->user('parent_admin')->parentBusiness, $plan, $request->validated());

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

    public function bulkUpdate(BulkUpdateProductPlansRequest $request): RedirectResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        $plans = ProductPlan::query()->where('parent_business_id', $parent->id)
            ->whereIn('id', $request->validated('plan_ids'))->get();
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

        return redirect()->route('parent-admin.product-plans.index')
            ->with('success', "{$plans->count()} product plans updated.");
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
