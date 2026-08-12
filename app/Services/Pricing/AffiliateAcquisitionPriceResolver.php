<?php

namespace App\Services\Pricing;

use App\Models\Affiliate;
use App\Models\ParentDefaultProfitRule;
use App\Models\ProductPlan;
use App\Models\ProductPlanParentPrice;
use App\Support\BrickMathRounding;
use Brick\Math\BigDecimal;

class AffiliateAcquisitionPriceResolver
{
    public function display(Affiliate $affiliate, ProductPlan $plan, ?ParentDefaultProfitRule $rule = null): string
    {
        $override = $plan->exists
            ? ProductPlanParentPrice::query()
                ->where('parent_business_id', $affiliate->parent_business_id)
                ->where('product_plan_id', $plan->id)
                ->where('parent_reseller_level_id', $affiliate->parent_reseller_level_id)
                ->first()
            : ($plan->relationLoaded('parentPrices')
                ? $plan->parentPrices->first(fn ($price) => (int) $price->parent_reseller_level_id === (int) $affiliate->parent_reseller_level_id)
                : null);

        if ($override) {
            return $this->money((string) $override->selling_price);
        }

        $rule ??= ParentDefaultProfitRule::query()
            ->where('parent_business_id', $affiliate->parent_business_id)
            ->where('parent_reseller_level_id', $affiliate->parent_reseller_level_id)
            ->where('product_id', $plan->product_plan_category?->product_id)
            ->first();

        if (! $rule) {
            return 'Not configured';
        }

        if ($rule->calculation_type === 'percent_discount') {
            return 'Dynamic ('.$this->money((string) $rule->value).'% discount)';
        }

        if (! is_numeric($plan->cost_price)) {
            return 'Not configured';
        }

        return $this->money((string) BigDecimal::of((string) $plan->cost_price)->plus((string) $rule->value));
    }

    private function money(string $value): string
    {
        return (string) BigDecimal::of($value)->toScale(2, BrickMathRounding::halfUp());
    }
}
