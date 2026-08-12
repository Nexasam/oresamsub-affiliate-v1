<?php

namespace App\Services\Pricing;

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\AffiliateServiceProfitCap;
use App\Models\ProductPlanParentPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AffiliatePlanProfitService
{
    public function update(Affiliate $affiliate, int $productPlanId, array $profits): AffiliateProductPlan
    {
        $levels = collect($profits)->mapWithKeys(fn ($value, $level) => [(int) $level => $value]);
        if ($levels->keys()->sort()->values()->all() !== range(1, 6)) {
            throw ValidationException::withMessages(['profits' => 'Provide exactly one profit for each customer level 1–6.']);
        }

        return DB::transaction(function () use ($affiliate, $productPlanId, $levels) {
            $affiliatePlan = AffiliateProductPlan::withoutGlobalScope('affiliate')
                ->with('product_plan.product_plan_category')
                ->where('affiliate_id', $affiliate->id)
                ->where('product_plan_id', $productPlanId)
                ->lockForUpdate()
                ->first();
            $plan = $affiliatePlan?->product_plan;
            if (! $affiliatePlan || ! $plan || (int) $plan->parent_business_id !== (int) $affiliate->parent_business_id) {
                throw ValidationException::withMessages(['plan_id' => 'This plan does not belong to the current affiliate and parent.']);
            }

            $productId = $plan->product_plan_category?->product_id;
            $caps = AffiliateServiceProfitCap::query()
                ->where('parent_business_id', $affiliate->parent_business_id)
                ->where('affiliate_id', $affiliate->id)
                ->where('product_id', $productId)
                ->pluck('max_value', 'customer_level');
            $planMaximum = ProductPlanParentPrice::query()
                ->where('parent_business_id', $affiliate->parent_business_id)
                ->where('product_plan_id', $plan->id)
                ->where('parent_reseller_level_id', $affiliate->parent_reseller_level_id)
                ->value('max_profit');

            foreach ($levels as $level => $value) {
                if (! is_numeric($value) || (float) $value < 0) {
                    throw ValidationException::withMessages(["profits.{$level}" => "Customer level {$level} profit must be zero or greater."]);
                }
                $limits = collect([$caps->get($level), $planMaximum])->filter(fn ($limit) => $limit !== null);
                $effectiveMaximum = $limits->isEmpty() ? null : $limits->min(fn ($limit) => (float) $limit);
                if ($effectiveMaximum === null) {
                    throw ValidationException::withMessages(["profits.{$level}" => "No parent profit maximum is configured for customer level {$level}."]);
                }
                if ((float) $value > $effectiveMaximum) {
                    throw ValidationException::withMessages(["profits.{$level}" => "Customer level {$level} profit cannot exceed {$effectiveMaximum}."]);
                }
                $affiliatePlan->{"user_level_{$level}_profit"} = $value;
            }

            $affiliatePlan->save();

            return $affiliatePlan->fresh();
        });
    }
}
