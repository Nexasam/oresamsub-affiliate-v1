<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\AffiliateProductPlanCategory;
use App\Models\AffiliateUserPlan;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Models\UserPlan;
use Illuminate\Support\Facades\DB;

class AffiliateCatalogGenerationService
{
    public function generateUserPlans(Affiliate $affiliate): array
    {
        $created = 0;
        $existing = 0;

        DB::transaction(function () use ($affiliate, &$created, &$existing) {
            foreach (UserPlan::whereRaw('CAST(plan_level AS UNSIGNED) BETWEEN 1 AND 6')
                ->orderByRaw('CAST(plan_level AS UNSIGNED)')->get() as $source) {
                $level = (int) $source->plan_level;
                $plan = AffiliateUserPlan::withoutGlobalScope('affiliate')->firstOrCreate(
                    ['affiliate_id' => $affiliate->id, 'canonical_plan_level' => $level],
                    [
                        'plan_level' => $level,
                        'user_plan_name' => $source->user_plan_name,
                        'updated_user_plan_name' => $source->updated_user_plan_name,
                        'is_default' => $source->is_default,
                        'visibility' => $source->visibility,
                    ]
                );
                $plan->wasRecentlyCreated ? $created++ : $existing++;
            }
        });

        return compact('created', 'existing');
    }

    public function generateCategories(Affiliate $affiliate): array
    {
        $created = 0;
        $existing = 0;

        DB::transaction(function () use ($affiliate, &$created, &$existing) {
            $categories = ProductPlanCategory::query()
                ->when(
                    $affiliate->parent_business_id,
                    fn ($query, $parentId) => $query->whereHas(
                        'product_plans',
                        fn ($plans) => $plans->where('parent_business_id', $parentId)
                    ),
                    fn ($query) => $query->whereRaw('1 = 0')
                )
                ->get();

            foreach ($categories as $source) {
                $category = AffiliateProductPlanCategory::withoutGlobalScope('affiliate')->firstOrCreate(
                    ['affiliate_id' => $affiliate->id, 'plan_category_id' => $source->id],
                    [
                        'product_plan_category_name' => $source->product_plan_category_name,
                        'referral_commission_feature' => $source->referral_commission_feature,
                        'referral_commission_method' => $source->referral_commission_method,
                        'referral_commission_value' => $source->referral_commission_value,
                        'product_id' => $source->product_id,
                        'is_hot_sales' => $source->is_hot_sales,
                        'visibility' => $source->visibility,
                        'network_id' => $source->network_id,
                    ]
                );
                $category->wasRecentlyCreated ? $created++ : $existing++;
            }
        });

        return compact('created', 'existing');
    }

    public function generateProductPlans(Affiliate $affiliate, AffiliateProductMarginService $marginService): array
    {
        $created = 0;
        $existing = 0;

        DB::transaction(function () use ($affiliate, $marginService, &$created, &$existing) {
            $plans = ProductPlan::query()
                ->when(
                    $affiliate->parent_business_id,
                    fn ($query, $parentId) => $query->where('parent_business_id', $parentId),
                    fn ($query) => $query->whereRaw('1 = 0')
                )
                ->get();

            foreach ($plans as $source) {
                $margin = $marginService->defaultFor($affiliate, $source);
                $plan = AffiliateProductPlan::withoutGlobalScope('affiliate')->firstOrCreate(
                    ['affiliate_id' => $affiliate->id, 'product_plan_id' => $source->id],
                    [
                        'product_plan_name' => $source->product_plan_name,
                        'data_size_in_mb' => $source->data_size_in_mb,
                        'validity_in_days' => $source->validity_in_days,
                        'user_level_1_profit' => $margin,
                        'user_level_2_profit' => $margin,
                        'user_level_3_profit' => $margin,
                        'user_level_4_profit' => $margin,
                        'user_level_5_profit' => $margin,
                        'user_level_6_profit' => $margin,
                        'commission_feature' => $source->commission_feature,
                        'upline_commission_option' => $source->upline_commission_option,
                        'upline_percentage_commission' => $source->upline_percentage_commission,
                        'upline_flat_commission' => $source->upline_flat_commission,
                        'upline_commission_cap' => $source->upline_commission_cap,
                        'visibility_from_admin' => $source->visibility,
                        'visibility' => $source->visibility,
                        'public_visibility' => $source->public_visibility,
                    ]
                );
                $plan->wasRecentlyCreated ? $created++ : $existing++;
            }
        });

        return compact('created', 'existing');
    }
}
