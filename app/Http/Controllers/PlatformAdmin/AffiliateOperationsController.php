<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\AffiliateProductPlanCategory;
use App\Models\WalletLog;
use App\Services\AffiliateProductMarginService;
use App\Services\AffiliateCatalogGenerationService;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AffiliateOperationsController extends Controller
{
    public function index(Affiliate $affiliate): View
    {
        return view('platform-admin.affiliates.operations', [
            'affiliate' => $affiliate,
            'affiliates' => Affiliate::orderBy('name')->get(['id', 'name']),
            'standalone' => false,
        ]);
    }

    public function standalone(Request $request): View
    {
        $affiliates = Affiliate::orderBy('name')->get(['id', 'name']);
        $affiliate = $request->filled('affiliate_id')
            ? Affiliate::findOrFail($request->integer('affiliate_id'))
            : $affiliates->first();

        abort_unless($affiliate, 404, 'No affiliates are available.');

        return view('platform-admin.affiliates.operations', [
            'affiliate' => $affiliate,
            'affiliates' => $affiliates,
            'standalone' => true,
        ]);
    }

    public function catalog(Request $request, Affiliate $affiliate): JsonResponse
    {
        $search = trim((string) $request->query('search'));

        $plans = AffiliateProductPlan::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $affiliate->id)
            ->with([
                'product_plan:id,product_plan_name,product_plan_category_id,profit_category,cost_price,cost_price_1,cost_price_2,cost_price_3,cost_price_4,cost_price_5,cost_price_6,cost_price_7,cost_price_8,cost_price_9,cost_price_10,cost_price_11,cost_price_12',
                'product_plan.product_plan_category:id,product_id,network_id,product_plan_category_name',
                'product_plan.product_plan_category.product:id,product_name,slug',
                'product_plan.product_plan_category.network:id,network_name',
            ])
            ->when($search, fn (Builder $query) => $query->where('product_plan_name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(25);

        $categories = AffiliateProductPlanCategory::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $affiliate->id)
            ->with(['product:id,product_name,slug', 'network:id,network_name'])
            ->orderBy('product_plan_category_name')
            ->get();

        return response()->json([
            'plans' => $plans,
            'categories' => $categories,
            'source_counts' => [
                'plans' => ProductPlan::count(),
                'categories' => ProductPlanCategory::count(),
            ],
            'generated_counts' => [
                'plans' => AffiliateProductPlan::withoutGlobalScope('affiliate')
                    ->where('affiliate_id', $affiliate->id)
                    ->count(),
                'categories' => AffiliateProductPlanCategory::withoutGlobalScope('affiliate')
                    ->where('affiliate_id', $affiliate->id)
                    ->count(),
            ],
        ]);
    }

    public function updatePlan(Request $request, Affiliate $affiliate, int $plan): JsonResponse
    {
        $plan = AffiliateProductPlan::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $affiliate->id)
            ->findOrFail($plan);

        $data = $request->validate([
            'product_plan_name' => ['sometimes', 'required', 'string', 'max:255'],
            'visibility' => ['sometimes', Rule::in([0, 1, '0', '1'])],
            'public_visibility' => ['sometimes', Rule::in([0, 1, '0', '1'])],
            'commission_feature' => ['sometimes', Rule::in([0, 1, '0', '1'])],
            'upline_commission_option' => ['sometimes', Rule::in(['flat', 'percent', 'percentage'])],
            'upline_flat_commission' => ['sometimes', 'numeric', 'min:0'],
            'upline_percentage_commission' => ['sometimes', 'numeric', 'between:0,100'],
            'upline_commission_cap' => ['sometimes', 'numeric', 'min:0'],
            'user_level_1_profit' => ['sometimes', 'numeric', 'min:0'],
            'user_level_2_profit' => ['sometimes', 'numeric', 'min:0'],
            'user_level_3_profit' => ['sometimes', 'numeric', 'min:0'],
            'user_level_4_profit' => ['sometimes', 'numeric', 'min:0'],
            'user_level_5_profit' => ['sometimes', 'numeric', 'min:0'],
            'user_level_6_profit' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $plan->update($data);

        return response()->json(['message' => 'Product plan updated.', 'plan' => $plan->fresh()]);
    }

    public function updateCategory(Request $request, Affiliate $affiliate, int $category): JsonResponse
    {
        $category = AffiliateProductPlanCategory::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $affiliate->id)
            ->findOrFail($category);

        $data = $request->validate([
            'product_plan_category_name' => ['sometimes', 'required', 'string', 'max:255'],
            'visibility' => ['sometimes', Rule::in([0, 1, '0', '1'])],
            'is_hot_sales' => ['sometimes', Rule::in([0, 1, true, false, '0', '1'])],
            'referral_commission_feature' => ['sometimes', Rule::in([0, 1, '0', '1'])],
            'referral_commission_method' => ['sometimes', Rule::in(['flat', 'percent', 'percentage'])],
            'referral_commission_value' => ['sometimes', 'numeric', 'min:0'],
        ]);

        if (
            isset($data['referral_commission_method'], $data['referral_commission_value'])
            && in_array($data['referral_commission_method'], ['percent', 'percentage'], true)
            && $data['referral_commission_value'] > 100
        ) {
            return response()->json(['message' => 'Percentage commission cannot exceed 100%.'], 422);
        }

        $category->update($data);

        return response()->json(['message' => 'Product plan category updated.', 'category' => $category->fresh()]);
    }

    public function walletLogs(Request $request, Affiliate $affiliate): JsonResponse
    {
        $logs = WalletLog::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $affiliate->id)
            ->with('user:id,first_name,last_name,email')
            ->when($request->search, fn (Builder $query, $search) => $query->where(function (Builder $query) use ($search) {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $user) => $user->where('email', 'like', "%{$search}%"));
            }))
            ->when($request->category, fn (Builder $query, $category) => $query->where('transaction_category', $category))
            ->latest()
            ->paginate(25);

        return response()->json($logs);
    }

    public function updateMargins(
        Request $request,
        Affiliate $affiliate,
        AffiliateProductMarginService $marginService
    ): JsonResponse {
        $data = $request->validate([
            'default_flat_profit_margin' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'default_percent_profit_margin' => ['required', 'numeric', 'between:0,100'],
            'apply_to_existing' => ['sometimes', 'boolean'],
        ]);

        $affiliate->update([
            'default_flat_profit_margin' => $data['default_flat_profit_margin'],
            'default_percent_profit_margin' => $data['default_percent_profit_margin'],
        ]);

        $updated = $request->boolean('apply_to_existing')
            ? $marginService->applyDefaultsToExisting($affiliate->fresh())
            : 0;

        return response()->json([
            'message' => $updated
                ? "Margin defaults saved and applied to {$updated} existing plans."
                : 'Margin defaults saved for new and newly synced plans.',
            'updated_plans' => $updated,
        ]);
    }

    public function generateCategories(
        Affiliate $affiliate,
        AffiliateCatalogGenerationService $generationService
    ): JsonResponse {
        $result = $generationService->generateCategories($affiliate);

        return response()->json([
            'message' => "{$result['created']} affiliate categories generated; {$result['existing']} already existed.",
            ...$result,
        ]);
    }

    public function generatePlans(
        Affiliate $affiliate,
        AffiliateCatalogGenerationService $generationService,
        AffiliateProductMarginService $marginService
    ): JsonResponse {
        $result = $generationService->generateProductPlans($affiliate, $marginService);

        return response()->json([
            'message' => "{$result['created']} affiliate product plans generated; {$result['existing']} already existed.",
            ...$result,
        ]);
    }
}
