<?php

namespace App\Services\Pricing;

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\AffiliateServiceProfitCap;
use App\Models\ParentDefaultProfitRule;
use App\Models\ProductPlan;
use App\Models\ProductPlanParentPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AffiliatePlanProfitService
{
    /** @return array{type:string, acquisition_discount:?float, parent_caps:array<int, float|null>, effective:array<int, float|null>} */
    public function limits(Affiliate $affiliate, ProductPlan $plan): array
    {
        $productId = $plan->product_plan_category?->product_id;
        $percentage = $plan->profit_category === 'percent';
        $caps = AffiliateServiceProfitCap::query()
            ->where('parent_business_id', $affiliate->parent_business_id)
            ->where('affiliate_id', $affiliate->id)
            ->where('product_id', $productId)
            ->pluck('max_value', 'customer_level');
        $parentPrice = ProductPlanParentPrice::query()
            ->where('parent_business_id', $affiliate->parent_business_id)
            ->where('product_plan_id', $plan->id)
            ->where('parent_reseller_level_id', $affiliate->parent_reseller_level_id)
            ->first();

        $acquisitionDiscount = null;
        if ($percentage) {
            if ($parentPrice && is_numeric($parentPrice->selling_price)) {
                $acquisitionDiscount = max(0.0, (1000.0 - (float) $parentPrice->selling_price) / 10.0);
            } else {
                $rule = ParentDefaultProfitRule::query()
                    ->where('parent_business_id', $affiliate->parent_business_id)
                    ->where('parent_reseller_level_id', $affiliate->parent_reseller_level_id)
                    ->where('product_id', $productId)
                    ->where('calculation_type', 'percent_discount')
                    ->first();
                if ($rule && is_numeric($rule->value)) {
                    $acquisitionDiscount = max(0.0, (float) $rule->value);
                }
            }
        }

        $economicMaximum = $acquisitionDiscount === null
            ? null
            : max(0.0, floor(($acquisitionDiscount - 0.01 + 0.0000001) * 100) / 100);
        $parentCaps = [];
        $effective = [];
        foreach (range(1, 6) as $level) {
            $parentCaps[$level] = $caps->has($level) ? (float) $caps->get($level) : null;
            $limits = collect([$parentCaps[$level]])->filter(fn ($limit) => $limit !== null);
            if ($percentage) {
                $limits = $limits->push($economicMaximum)->filter(fn ($limit) => $limit !== null);
            } elseif ($parentPrice?->max_profit !== null) {
                $limits->push((float) $parentPrice->max_profit);
            }
            $effective[$level] = $limits->isEmpty() ? null : round((float) $limits->min(), 2);
        }

        return [
            'type' => $percentage ? 'percent' : 'flat',
            'acquisition_discount' => $acquisitionDiscount === null ? null : round($acquisitionDiscount, 2),
            'parent_caps' => $parentCaps,
            'effective' => $effective,
        ];
    }

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

            $limits = $this->limits($affiliate, $plan);

            foreach ($levels as $level => $value) {
                if (! is_numeric($value) || (float) $value < 0) {
                    throw ValidationException::withMessages(["profits.{$level}" => "Customer level {$level} profit must be zero or greater."]);
                }
                $effectiveMaximum = $limits['effective'][$level] ?? null;
                if ($effectiveMaximum === null) {
                    throw ValidationException::withMessages(["profits.{$level}" => "No parent profit maximum is configured for customer level {$level}."]);
                }
                if ((float) $value > $effectiveMaximum) {
                    $setting = $limits['type'] === 'percent' ? 'discount' : 'profit';
                    $suffix = $limits['type'] === 'percent' ? '%' : '';
                    throw ValidationException::withMessages(["profits.{$level}" => "Customer level {$level} {$setting} cannot exceed {$effectiveMaximum}{$suffix}."]);
                }
                $affiliatePlan->{"user_level_{$level}_profit"} = $value;
            }

            $affiliatePlan->save();

            return $affiliatePlan->fresh();
        });
    }
}
