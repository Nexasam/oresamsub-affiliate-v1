<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentAdmin\UpdateProductPlanPricesRequest;
use App\Http\Requests\ParentAdmin\UpdateResellerLevelsRequest;
use App\Models\ProductPlan;
use App\Services\ParentAdmin\ParentCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function __construct(private readonly ParentCatalogService $catalog) {}

    public function index(): View
    {
        return view('parent-admin.pricing.index');
    }

    public function data(Request $request): JsonResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;

        return response()->json([
            'levels' => $parent->resellerLevels()->where('status', 'active')->orderBy('position')->get(),
            'plans' => $this->catalog->plans($parent),
        ]);
    }

    public function updateLevels(UpdateResellerLevelsRequest $request): JsonResponse
    {
        $levels = $this->catalog->replaceLevels(
            $request->user('parent_admin')->parentBusiness,
            $request->validated('levels'),
        );

        return response()->json(['message' => 'Reseller levels updated.', 'levels' => $levels]);
    }

    public function generateSix(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Missing reseller levels generated.',
            ...$this->catalog->generateSixLevels($request->user('parent_admin')->parentBusiness),
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
}
