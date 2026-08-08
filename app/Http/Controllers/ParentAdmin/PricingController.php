<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentAdmin\UpdateDefaultProfitRulesRequest;
use App\Http\Requests\ParentAdmin\UpdateProductPlanPricesRequest;
use App\Http\Requests\ParentAdmin\UpdateResellerLevelsRequest;
use App\Models\ParentResellerLevel;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Services\ParentAdmin\ParentCatalogService;
use App\Services\ParentAdmin\ParentProfitRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function __construct(
        private readonly ParentCatalogService $catalog,
        private readonly ParentProfitRuleService $profitRules,
    ) {}

    public function index(): View
    {
        return view('parent-admin.pricing.index');
    }

    public function data(Request $request): JsonResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'product_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'pricing_status' => ['nullable', Rule::in(['inherited', 'custom'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json([
            'levels' => $parent->resellerLevels()->where('status', 'active')->orderBy('position')->get(),
            'plans' => $this->catalog->plans($parent, 50, $filters),
            'defaults' => $this->profitRules->ensureDefaults($parent),
            'products' => $this->profitRules->supportedProducts(),
            'categories' => ProductPlanCategory::query()
                ->whereHas('product_plans', fn ($plans) => $plans->where('parent_business_id', $parent->id))
                ->with('network:id,network_name')->orderBy('product_plan_category_name')->get(),
        ]);
    }

    public function updateDefaults(UpdateDefaultProfitRulesRequest $request): JsonResponse
    {
        $defaults = $this->profitRules->replaceDefaults(
            $request->user('parent_admin')->parentBusiness,
            $request->validated('rules'),
        );

        return response()->json(['message' => 'Default profit settings updated.', 'defaults' => $defaults]);
    }

    public function updateLevels(UpdateResellerLevelsRequest $request): JsonResponse
    {
        $levels = $this->catalog->replaceLevels(
            $request->user('parent_admin')->parentBusiness,
            $request->validated('levels'),
        );
        $this->profitRules->ensureDefaults($request->user('parent_admin')->parentBusiness);

        return response()->json(['message' => 'Reseller levels updated.', 'levels' => $levels]);
    }

    public function generateSix(Request $request): JsonResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        $result = $this->catalog->generateSixLevels($parent);
        $this->profitRules->ensureDefaults($parent);

        return response()->json([
            'message' => 'Missing reseller levels generated.',
            ...$result,
        ]);
    }

    public function updatePrices(UpdateProductPlanPricesRequest $request, ProductPlan $plan): JsonResponse
    {
        $prices = $this->catalog->updatePrices(
            $request->user('parent_admin')->parentBusiness,
            $plan,
            $request->validated('prices'),
        );

        return response()->json(['message' => 'Plan prices updated.', 'prices' => $prices]);
    }

    public function clearOverride(Request $request, ProductPlan $plan, ParentResellerLevel $level): JsonResponse
    {
        $this->catalog->clearPriceOverride($request->user('parent_admin')->parentBusiness, $plan, $level);

        return response()->json(['message' => 'Plan now uses the default profit setting.']);
    }
}
