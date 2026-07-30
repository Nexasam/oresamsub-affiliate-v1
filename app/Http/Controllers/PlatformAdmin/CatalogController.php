<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(): View
    {
        return view('platform-admin.catalog.index');
    }

    public function data(): JsonResponse
    {
        return response()->json([
            'categories' => ProductPlanCategory::with(['product:id,product_name', 'network:id,network_name'])->orderBy('product_plan_category_name')->get(),
            'plans' => ProductPlan::with([
                'product_plan_category:id,product_id,network_id,product_plan_category_name',
                'product_plan_category.product:id,product_name',
                'product_plan_category.network:id,network_name',
            ])->latest()->paginate(50),
        ]);
    }

    public function updateCategory(Request $request, ProductPlanCategory $category): JsonResponse
    {
        $category->update($request->validate([
            'product_plan_category_name' => ['sometimes', 'required', 'string', 'max:255'],
            'visibility' => ['sometimes', Rule::in([0, 1, '0', '1'])],
            'is_hot_sales' => ['sometimes', Rule::in([0, 1, true, false, '0', '1'])],
        ]));

        return response()->json(['message' => 'Master category updated.']);
    }

    public function updatePlan(Request $request, ProductPlan $plan): JsonResponse
    {
        $rules = [
            'product_plan_name' => ['sometimes', 'required', 'string', 'max:255'],
            'profit_category' => ['sometimes', Rule::in(['flat', 'percent'])],
            'cost_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'admin_cost_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'visibility' => ['sometimes', Rule::in([0, 1, '0', '1'])],
            'public_visibility' => ['sometimes', Rule::in([0, 1, '0', '1'])],
            'affiliate_visibility' => ['sometimes', Rule::in([0, 1, '0', '1'])],
        ];

        foreach (range(1, 12) as $level) {
            $rules["aff_level_{$level}_max_profit"] = ['sometimes', 'nullable', 'numeric', 'min:0'];
        }

        $plan->update($request->validate($rules));

        return response()->json([
            'message' => 'Global product plan updated.',
            'plan' => $plan->fresh(),
        ]);
    }
}
