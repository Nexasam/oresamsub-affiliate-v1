<?php

namespace App\Services\Pricing;

use App\Models\Affiliate;
use App\Models\ParentDefaultProfitRule;
use App\Models\ProductPlan;
use App\Models\ProductPlanParentPrice;
use App\Models\ParentResellerLevel;
use App\Support\BrickMathRounding;
use Brick\Math\BigDecimal;

class AffiliateAcquisitionPriceResolver
{
    public function display(Affiliate $affiliate, ProductPlan $plan, ?ParentDefaultProfitRule $rule = null): string
    {
        return $this->resolve($affiliate, $plan, $rule)['price'];
    }

    /** @return array{price:string, source:string, level_id:int, level_position:int|null, level_name:string|null} */
    public function resolve(Affiliate $affiliate, ProductPlan $plan, ?ParentDefaultProfitRule $rule = null): array
    {
        $level = $affiliate->exists
            ? ParentResellerLevel::query()
                ->whereKey($affiliate->parent_reseller_level_id)
                ->where('parent_business_id', $affiliate->parent_business_id)
                ->first()
            : new ParentResellerLevel(['id' => $affiliate->parent_reseller_level_id, 'position' => 0, 'name' => null]);
        if (! $level) {
            return ['price' => 'Invalid reseller level', 'source' => 'invalid', 'level_id' => (int) $affiliate->parent_reseller_level_id, 'level_position' => null, 'level_name' => null];
        }

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
            return $this->result($this->money((string) $override->selling_price), 'custom', $level);
        }

        $rule ??= ParentDefaultProfitRule::query()
            ->where('parent_business_id', $affiliate->parent_business_id)
            ->where('parent_reseller_level_id', $affiliate->parent_reseller_level_id)
            ->where('product_id', $plan->product_plan_category?->product_id)
            ->first();

        if (! $rule) {
            return $this->result('Not configured', 'missing', $level);
        }

        if ($rule->calculation_type === 'percent_discount') {
            return $this->result('Dynamic ('.$this->money((string) $rule->value).'% discount)', 'default', $level);
        }

        if (! is_numeric($plan->cost_price)) {
            return $this->result('Not configured', 'missing', $level);
        }

        return $this->result($this->money((string) BigDecimal::of((string) $plan->cost_price)->plus((string) $rule->value)), 'default', $level);
    }

    private function result(string $price, string $source, ParentResellerLevel $level): array
    {
        return ['price' => $price, 'source' => $source, 'level_id' => (int) $level->id, 'level_position' => $level->position ? (int) $level->position : null, 'level_name' => $level->name];
    }

    private function money(string $value): string
    {
        return (string) BigDecimal::of($value)->toScale(2, BrickMathRounding::halfUp());
    }
}
