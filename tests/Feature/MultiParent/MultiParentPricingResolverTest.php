<?php

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\AffiliateServiceProfitCap;
use App\Models\ParentBusiness;
use App\Models\ParentDefaultProfitRule;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Models\ProductPlanParentPrice;
use App\Services\Pricing\MultiParentPricingResolver;
use Illuminate\Validation\ValidationException;

function resolverFixture(string $suffix, string $service = 'Data', string $cost = '100.00'): array
{
    $parent = ParentBusiness::create(['name' => "Resolver {$suffix}", 'slug' => "resolver-{$suffix}"]);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $product = Product::create(['api_id' => "resolver-product-{$suffix}", 'product_name' => $service, 'slug' => strtolower($service)."-resolver-{$suffix}"]);
    $category = ProductPlanCategory::create(['api_id' => "resolver-category-{$suffix}", 'product_id' => $product->id, 'product_plan_category_name' => "Category {$suffix}"]);
    $plan = ProductPlan::create(['parent_business_id' => $parent->id, 'product_plan_category_id' => $category->id, 'product_plan_name' => "Plan {$suffix}", 'cost_price' => $cost, 'profit_category' => in_array($service, ['Airtime', 'Electricity']) ? 'percent' : 'flat']);
    $affiliate = Affiliate::create(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id, 'name' => "Affiliate {$suffix}", 'slug' => "resolver-affiliate-{$suffix}", 'affiliate_plan_id' => 1, 'ip_address' => "resolver-{$suffix}", 'contact_phone' => "084{$suffix}", 'contact_email' => "resolver-{$suffix}@example.test", 'parent_key' => "resolver-key-{$suffix}", 'parent_email' => "resolver-parent-{$suffix}@example.test"]);
    $affiliatePlan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create(['affiliate_id' => $affiliate->id, 'product_plan_id' => $plan->id, 'product_plan_name' => $plan->product_plan_name, 'visibility' => 1, 'visibility_from_admin' => 1, 'user_level_1_profit' => $service === 'Data' ? 10 : 1]);

    return compact('parent', 'level', 'product', 'plan', 'affiliate', 'affiliatePlan');
}

it('combines provider cost parent default and affiliate flat margin', function () {
    $f = resolverFixture('4001');
    ParentDefaultProfitRule::create(['parent_business_id' => $f['parent']->id, 'parent_reseller_level_id' => $f['level']->id, 'product_id' => $f['product']->id, 'calculation_type' => 'flat', 'value' => 20]);
    AffiliateServiceProfitCap::create(['parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id, 'product_id' => $f['product']->id, 'customer_level' => 1, 'calculation_type' => 'flat', 'max_value' => 70]);

    $price = app(MultiParentPricingResolver::class)->resolve($f['affiliate'], $f['affiliatePlan'], 1);

    expect($price)->toMatchArray(['provider_cost' => '100.00', 'parent_profit' => '20.00', 'affiliate_acquisition_price' => '120.00', 'affiliate_profit' => '10.00', 'customer_selling_price' => '130.00', 'parent_pricing_source' => 'default', 'affiliate_pricing_type' => 'flat']);
});

it('uses a custom parent price before applying the affiliate margin', function () {
    $f = resolverFixture('4002');
    ParentDefaultProfitRule::create(['parent_business_id' => $f['parent']->id, 'parent_reseller_level_id' => $f['level']->id, 'product_id' => $f['product']->id, 'calculation_type' => 'flat', 'value' => 20]);
    ProductPlanParentPrice::create(['parent_business_id' => $f['parent']->id, 'product_plan_id' => $f['plan']->id, 'parent_reseller_level_id' => $f['level']->id, 'selling_price' => 125, 'max_profit' => 15]);

    $price = app(MultiParentPricingResolver::class)->resolve($f['affiliate'], $f['affiliatePlan'], 1);

    expect($price['affiliate_acquisition_price'])->toBe('125.00')
        ->and($price['parent_profit'])->toBe('25.00')
        ->and($price['customer_selling_price'])->toBe('135.00')
        ->and($price['parent_pricing_source'])->toBe('custom');
});

it('calculates percentage services from face amount and returns realized profits', function () {
    $f = resolverFixture('4003', 'Airtime', '950.00');
    ParentDefaultProfitRule::create(['parent_business_id' => $f['parent']->id, 'parent_reseller_level_id' => $f['level']->id, 'product_id' => $f['product']->id, 'calculation_type' => 'percent_discount', 'value' => 3]);
    AffiliateServiceProfitCap::create(['parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id, 'product_id' => $f['product']->id, 'customer_level' => 1, 'calculation_type' => 'percent', 'max_value' => 1]);

    $price = app(MultiParentPricingResolver::class)->resolve($f['affiliate'], $f['affiliatePlan'], 1, '1000.00');

    expect($price)->toMatchArray(['provider_cost' => '950.00', 'affiliate_acquisition_price' => '970.00', 'parent_profit' => '20.00', 'affiliate_profit' => '20.00', 'customer_selling_price' => '990.00', 'affiliate_pricing_type' => 'percent_discount']);
});

it('scales a percentage service reference cost proportionally for variable face amounts', function () {
    $f = resolverFixture('4006', 'Airtime', '950.00');
    ParentDefaultProfitRule::create(['parent_business_id' => $f['parent']->id, 'parent_reseller_level_id' => $f['level']->id, 'product_id' => $f['product']->id, 'calculation_type' => 'percent_discount', 'value' => 3]);
    AffiliateServiceProfitCap::create(['parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id, 'product_id' => $f['product']->id, 'customer_level' => 1, 'calculation_type' => 'percent', 'max_value' => 1]);

    $price = app(MultiParentPricingResolver::class)->resolve($f['affiliate'], $f['affiliatePlan'], 1, '500.00');

    expect($price)->toMatchArray([
        'provider_cost' => '475.00', 'affiliate_acquisition_price' => '485.00',
        'parent_profit' => '10.00', 'affiliate_profit' => '10.00', 'customer_selling_price' => '495.00',
    ]);
});

it('rejects margins above the parent cap and cross parent plans', function () {
    $f = resolverFixture('4004');
    ParentDefaultProfitRule::create(['parent_business_id' => $f['parent']->id, 'parent_reseller_level_id' => $f['level']->id, 'product_id' => $f['product']->id, 'calculation_type' => 'flat', 'value' => 20]);
    AffiliateServiceProfitCap::create(['parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id, 'product_id' => $f['product']->id, 'customer_level' => 1, 'calculation_type' => 'flat', 'max_value' => 5]);

    expect(fn () => app(MultiParentPricingResolver::class)->resolve($f['affiliate'], $f['affiliatePlan'], 1))->toThrow(ValidationException::class);

    $other = resolverFixture('4005');
    expect(fn () => app(MultiParentPricingResolver::class)->resolve($f['affiliate'], $other['affiliatePlan'], 1))->toThrow(ValidationException::class);
});
