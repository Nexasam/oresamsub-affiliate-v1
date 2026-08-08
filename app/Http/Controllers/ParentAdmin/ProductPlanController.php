<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentAdmin\StoreProductPlanRequest;
use App\Http\Requests\ParentAdmin\UpdateProductPlanRequest;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Services\ParentAdmin\ParentCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductPlanController extends Controller
{
    public function __construct(private readonly ParentCatalogService $catalog) {}

    public function index(): View
    {
        return view('parent-admin.product-plans.index');
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
        ]);
    }

    public function store(StoreProductPlanRequest $request): JsonResponse
    {
        $plan = $this->catalog->createPlan(
            $request->user('parent_admin')->parentBusiness,
            $request->validated(),
        );

        return response()->json([
            'message' => 'Product plan added.',
            'plan' => $plan->fresh(['product_plan_category.product', 'product_plan_category.network']),
        ], 201);
    }

    public function update(UpdateProductPlanRequest $request, ProductPlan $plan): JsonResponse
    {
        $plan = $this->catalog->updatePlan(
            $request->user('parent_admin')->parentBusiness,
            $plan,
            $request->validated(),
        );

        return response()->json(['message' => 'Product plan updated.', 'plan' => $plan]);
    }
}
