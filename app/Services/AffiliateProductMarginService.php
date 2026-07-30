<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\ProductPlan;

class AffiliateProductMarginService
{
    public function defaultFor(Affiliate $affiliate, ProductPlan $productPlan): float
    {
        return $productPlan->profit_category === 'flat'
            ? (float) ($affiliate->default_flat_profit_margin ?? 50)
            : (float) ($affiliate->default_percent_profit_margin ?? 1);
    }

    public function applyToPlan(AffiliateProductPlan $affiliatePlan, float $margin): void
    {
        $values = [];

        for ($level = 1; $level <= 6; $level++) {
            $values["user_level_{$level}_profit"] = $margin;
        }

        $affiliatePlan->update($values);
    }

    public function applyDefaultsToExisting(Affiliate $affiliate): int
    {
        $plans = AffiliateProductPlan::withoutGlobalScope('affiliate')
            ->with('product_plan:id,profit_category')
            ->where('affiliate_id', $affiliate->id)
            ->get();

        foreach ($plans as $plan) {
            if ($plan->product_plan) {
                $this->applyToPlan($plan, $this->defaultFor($affiliate, $plan->product_plan));
            }
        }

        return $plans->count();
    }
}
