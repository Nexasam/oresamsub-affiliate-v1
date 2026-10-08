<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\ProductPlan;
use App\Services\Pricing\AffiliatePlanProfitService;
use Illuminate\Support\Facades\DB;

class AffiliateProductPlanSyncService
{
    public function __construct(
        private readonly AffiliateProductMarginService $marginService,
        private readonly AffiliatePlanProfitService $profitService,
    ) {}

    /** @return array{created:int, updated:int} */
    public function sync(Affiliate $affiliate): array
    {
        return DB::transaction(function () use ($affiliate): array {
            $counts = ['created' => 0, 'updated' => 0];

            ProductPlan::query()->where('parent_business_id', $affiliate->parent_business_id)
                ->orderBy('id')->chunkById(200, function ($plans) use ($affiliate, &$counts): void {
                    $existing = AffiliateProductPlan::withoutGlobalScope('affiliate')
                        ->where('affiliate_id', $affiliate->id)
                        ->whereIn('product_plan_id', $plans->pluck('id'))
                        ->get()->keyBy('product_plan_id');

                    foreach ($plans as $plan) {
                        $affiliatePlan = $existing->get($plan->id);
                        if ($affiliatePlan) {
                            $affiliatePlan->update(array_merge([
                                'product_plan_name' => $plan->product_plan_name,
                                'data_size_in_mb' => $plan->data_size_in_mb,
                                'validity_in_days' => $plan->validity_in_days,
                            ], $this->clampedProfits($affiliate, $plan, $affiliatePlan)));
                            $counts['updated']++;
                            continue;
                        }

                        $margin = $this->marginService->defaultFor($affiliate, $plan);
                        $attributes = [
                            'affiliate_id' => $affiliate->id,
                            'product_plan_id' => $plan->id,
                            'product_plan_name' => $plan->product_plan_name,
                            'user_level_1_profit' => $margin,
                            'user_level_2_profit' => $margin,
                            'user_level_3_profit' => $margin,
                            'user_level_4_profit' => $margin,
                            'user_level_5_profit' => $margin,
                            'user_level_6_profit' => $margin,
                            'data_size_in_mb' => $plan->data_size_in_mb,
                            'validity_in_days' => $plan->validity_in_days,
                            'visibility' => true,
                            'visibility_from_admin' => true,
                            'public_visibility' => true,
                        ];
                        $draft = new AffiliateProductPlan($attributes);
                        $attributes = array_merge($attributes, $this->clampedProfits($affiliate, $plan, $draft));
                        AffiliateProductPlan::withoutGlobalScope('affiliate')->create($attributes);
                        $counts['created']++;
                    }
                });

            return $counts;
        });
    }

    private function clampedProfits(Affiliate $affiliate, ProductPlan $plan, AffiliateProductPlan $affiliatePlan): array
    {
        $limits = $this->profitService->limits($affiliate, $plan)['effective'];
        $updates = [];

        foreach (range(1, 6) as $level) {
            $field = "user_level_{$level}_profit";
            $current = (float) ($affiliatePlan->{$field} ?? 0);
            $limit = $limits[$level] ?? null;
            $updates[$field] = $limit === null ? $current : min($current, (float) $limit);
        }

        return $updates;
    }
}
