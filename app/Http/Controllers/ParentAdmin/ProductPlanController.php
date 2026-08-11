<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentAdmin\BulkStoreProductPlansRequest;
use App\Http\Requests\ParentAdmin\StoreProductPlanRequest;
use App\Http\Requests\ParentAdmin\UpdateProductPlanRequest;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Services\ParentAdmin\ParentCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductPlanController extends Controller
{
    public function __construct(private readonly ParentCatalogService $catalog) {}

    public function index(Request $request): View
    {
        $parent = $request->user('parent_admin')->parentBusiness;

        return view('parent-admin.product-plans.index', [
            'plans' => $this->catalog->plans($parent),
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
