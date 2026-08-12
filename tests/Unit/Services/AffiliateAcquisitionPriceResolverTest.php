<?php

use App\Models\Affiliate;
use App\Models\ParentDefaultProfitRule;
use App\Models\ProductPlan;
use App\Models\ProductPlanParentPrice;
use App\Services\Pricing\AffiliateAcquisitionPriceResolver;
use Tests\TestCase;

uses(TestCase::class);

it('uses the parent plan override as the affiliate acquisition price', function () {
    $affiliate = new Affiliate(['parent_business_id' => 8, 'parent_reseller_level_id' => 4]);
    $plan = new ProductPlan(['id' => 12, 'parent_business_id' => 8, 'cost_price' => '100.00']);
    $override = new ProductPlanParentPrice(['parent_reseller_level_id' => 4, 'selling_price' => '127.50']);
    $plan->setRelation('parentPrices', collect([$override]));

    expect(app(AffiliateAcquisitionPriceResolver::class)->display($affiliate, $plan))->toBe('127.50');
});

it('adds a flat parent default to provider cost when no override exists', function () {
    $affiliate = new Affiliate(['parent_business_id' => 8, 'parent_reseller_level_id' => 4]);
    $plan = new ProductPlan(['parent_business_id' => 8, 'cost_price' => '100.00']);
    $plan->setRelation('parentPrices', collect());
    $rule = new ParentDefaultProfitRule(['calculation_type' => 'flat', 'value' => '20.00']);

    expect(app(AffiliateAcquisitionPriceResolver::class)->display($affiliate, $plan, $rule))->toBe('120.00');
});

it('labels percentage acquisition pricing as dynamic without a face amount', function () {
    $affiliate = new Affiliate(['parent_business_id' => 8, 'parent_reseller_level_id' => 4]);
    $plan = new ProductPlan(['parent_business_id' => 8, 'cost_price' => '950.00']);
    $plan->setRelation('parentPrices', collect());
    $rule = new ParentDefaultProfitRule(['calculation_type' => 'percent_discount', 'value' => '3.00']);

    expect(app(AffiliateAcquisitionPriceResolver::class)->display($affiliate, $plan, $rule))->toBe('Dynamic (3.00% discount)');
});
