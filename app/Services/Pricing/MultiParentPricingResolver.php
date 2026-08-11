<?php

namespace App\Services\Pricing;

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\AffiliateServiceProfitCap;
use App\Models\ParentDefaultProfitRule;
use App\Models\ProductPlanParentPrice;
use App\Services\ParentAdmin\ParentProfitRuleService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Validation\ValidationException;

class MultiParentPricingResolver
{
    public function __construct(private readonly ParentProfitRuleService $profitRules) {}

    /** @return array<string, int|string> */
    public function resolve(Affiliate $affiliate, AffiliateProductPlan $affiliatePlan, int $customerLevel, ?string $faceAmount = null): array
    {
        if ($customerLevel < 1 || $customerLevel > 6) {
            $this->fail('customer_level', 'Customer level must be between 1 and 6.');
        }

        $affiliatePlan->loadMissing('product_plan.product_plan_category.product');
        $plan = $affiliatePlan->product_plan;
        $product = $plan?->product_plan_category?->product;

        if (! $plan || ! $product
            || (int) $affiliatePlan->affiliate_id !== (int) $affiliate->id
            || ! $affiliate->parent_business_id
            || (int) $plan->parent_business_id !== (int) $affiliate->parent_business_id) {
            $this->fail('affiliate_product_plan', 'The affiliate plan does not belong to this affiliate parent.');
        }
        if (! $affiliate->parent_reseller_level_id) {
            $this->fail('parent_reseller_level', 'The affiliate has no parent reseller level.');
        }
        if (! is_numeric($plan->cost_price)) {
            $this->fail('provider_cost', 'The product plan has no valid provider cost.');
        }

        $providerCost = $this->decimal((string) $plan->cost_price);
        $custom = ProductPlanParentPrice::query()
            ->where('parent_business_id', $affiliate->parent_business_id)
            ->where('product_plan_id', $plan->id)
            ->where('parent_reseller_level_id', $affiliate->parent_reseller_level_id)
            ->first();

        if ($custom) {
            $acquisitionPrice = $this->decimal($custom->selling_price);
            $parentSource = 'custom';
            $parentRuleType = 'custom_price';
            $parentRuleValue = $this->money($acquisitionPrice->minus($providerCost));
        } else {
            $rule = ParentDefaultProfitRule::query()
                ->where('parent_business_id', $affiliate->parent_business_id)
                ->where('parent_reseller_level_id', $affiliate->parent_reseller_level_id)
                ->where('product_id', $product->id)
                ->first();
            if (! $rule) {
                $this->fail('parent_pricing', 'No parent default or custom price is configured for this plan.');
            }

            $parentSource = 'default';
            $parentRuleType = $rule->calculation_type;
            $parentRuleValue = $this->money($this->decimal($rule->value));
            $acquisitionPrice = $rule->calculation_type === 'flat'
                ? $providerCost->plus($this->decimal($rule->value))
                : $this->discounted($this->requiredFaceAmount($faceAmount), $this->decimal($rule->value));
        }

        if ($acquisitionPrice->isLessThan($providerCost)) {
            $this->fail('parent_pricing', 'The affiliate acquisition price cannot be below the provider cost.');
        }

        $marginField = "user_level_{$customerLevel}_profit";
        $margin = $this->decimal((string) ($affiliatePlan->{$marginField} ?? 0));
        $service = $this->profitRules->serviceKey($product);
        $affiliateType = in_array($service, ['airtime', 'electricity'], true) ? 'percent_discount' : 'flat';

        $cap = AffiliateServiceProfitCap::query()
            ->where('parent_business_id', $affiliate->parent_business_id)
            ->where('affiliate_id', $affiliate->id)
            ->where('product_id', $product->id)
            ->where('customer_level', $customerLevel)
            ->first();
        if (! $cap && ! $custom?->max_profit) {
            $this->fail('affiliate_profit', 'No affiliate profit maximum is configured for this service and customer level.');
        }
        if ($cap && $margin->isGreaterThan($this->decimal($cap->max_value))) {
            $this->fail('affiliate_profit', 'The affiliate margin exceeds the parent-approved service maximum.');
        }

        $customerPrice = $affiliateType === 'flat'
            ? $acquisitionPrice->plus($margin)
            : $this->discounted($this->requiredFaceAmount($faceAmount), $margin);
        $affiliateProfit = $customerPrice->minus($acquisitionPrice);
        if ($affiliateProfit->isNegative()) {
            $this->fail('affiliate_profit', 'The configured customer price would sell below the affiliate acquisition price.');
        }
        if ($custom?->max_profit !== null && $affiliateProfit->isGreaterThan($this->decimal($custom->max_profit))) {
            $this->fail('affiliate_profit', 'The realized affiliate profit exceeds this plan reseller maximum.');
        }

        return [
            'provider_cost' => $this->money($providerCost),
            'parent_profit' => $this->money($acquisitionPrice->minus($providerCost)),
            'affiliate_acquisition_price' => $this->money($acquisitionPrice),
            'affiliate_profit' => $this->money($affiliateProfit),
            'customer_selling_price' => $this->money($customerPrice),
            'parent_pricing_source' => $parentSource,
            'parent_pricing_type' => $parentRuleType,
            'parent_pricing_value' => $parentRuleValue,
            'affiliate_pricing_type' => $affiliateType,
            'affiliate_pricing_value' => $this->money($margin),
            'parent_business_id' => (int) $affiliate->parent_business_id,
            'parent_reseller_level_id' => (int) $affiliate->parent_reseller_level_id,
            'affiliate_id' => (int) $affiliate->id,
            'affiliate_product_plan_id' => (int) $affiliatePlan->id,
            'product_plan_id' => (int) $plan->id,
            'customer_level' => $customerLevel,
        ];
    }

    private function discounted(BigDecimal $amount, BigDecimal $percent): BigDecimal
    {
        return $amount->minus($amount->multipliedBy($percent)->dividedBy(100, 8, RoundingMode::HalfUp));
    }

    private function requiredFaceAmount(?string $faceAmount): BigDecimal
    {
        if ($faceAmount === null || ! is_numeric($faceAmount) || $this->decimal($faceAmount)->isLessThanOrEqualTo(0)) {
            $this->fail('face_amount', 'A positive transaction face amount is required for percentage-priced services.');
        }

        return $this->decimal($faceAmount);
    }

    private function decimal(string $value): BigDecimal
    {
        return BigDecimal::of($value);
    }

    private function money(BigDecimal $value): string
    {
        return (string) $value->toScale(2, RoundingMode::HalfUp);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
