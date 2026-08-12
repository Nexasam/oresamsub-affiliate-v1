<?php

use App\Http\Services\DataPlansService;
use App\Models\AffiliateProcessingProfile;
use App\Models\AffiliateServiceProfitCap;
use App\Models\AffiliateUserPlan;
use App\Models\ParentDefaultProfitRule;
use App\Models\ProductPlanParentPrice;
use App\Models\User;
use App\Services\Pricing\AffiliateAcquisitionPriceResolver;

it('returns custom parent acquisition price plus affiliate customer profit', function () {
    $f = affiliateProfitFixture('catalogue1');
    $customerPlan = AffiliateUserPlan::create(['affiliate_id' => $f['affiliate']->id, 'user_plan_name' => 'Basic', 'plan_level' => 1, 'visibility' => 1]);
    $customer = User::factory()->create(['affiliate_id' => $f['affiliate']->id, 'user_plan_id' => $customerPlan->id]);
    AffiliateProcessingProfile::create(['affiliate_id' => $f['affiliate']->id, 'parent_business_id' => $f['parent']->id, 'management_mode' => 'parent_managed', 'processing_engine' => 'multi_parent']);
    ParentDefaultProfitRule::create(['parent_business_id' => $f['parent']->id, 'parent_reseller_level_id' => $f['level']->id, 'product_id' => $f['product']->id, 'calculation_type' => 'flat', 'value' => 70]);
    ProductPlanParentPrice::create(['parent_business_id' => $f['parent']->id, 'product_plan_id' => $f['plan']->id, 'parent_reseller_level_id' => $f['level']->id, 'selling_price' => 540, 'max_profit' => 100]);
    AffiliateServiceProfitCap::create(['parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id, 'product_id' => $f['product']->id, 'customer_level' => 1, 'calculation_type' => 'flat', 'max_value' => 70]);
    $f['affiliatePlan']->update(['user_level_1_profit' => 50]);

    $price = app(DataPlansService::class)->get_customer_price_per_plan(['plan_details' => $f['affiliatePlan']->fresh(), 'product_id' => $f['product']->id, 'user' => $customer, 'network_id' => '', 'amount' => 0]);

    expect($price['message'])->toBe('590.00');
});

it('reads the exact persisted custom override for the affiliates assigned reseller level', function () {
    $f = affiliateProfitFixture('catalogue2');
    $level2 = $f['parent']->resellerLevels()->create(['name' => 'Silver', 'position' => 2, 'status' => 'active']);
    $level3 = $f['parent']->resellerLevels()->create(['name' => 'Gold', 'position' => 3, 'status' => 'active']);
    $f['affiliate']->update(['parent_reseller_level_id' => $level3->id]);
    ParentDefaultProfitRule::create(['parent_business_id' => $f['parent']->id, 'parent_reseller_level_id' => $level3->id, 'product_id' => $f['product']->id, 'calculation_type' => 'flat', 'value' => 50]);
    ProductPlanParentPrice::create(['parent_business_id' => $f['parent']->id, 'product_plan_id' => $f['plan']->id, 'parent_reseller_level_id' => $level2->id, 'selling_price' => 565]);
    ProductPlanParentPrice::create(['parent_business_id' => $f['parent']->id, 'product_plan_id' => $f['plan']->id, 'parent_reseller_level_id' => $level3->id, 'selling_price' => 595]);

    expect(app(AffiliateAcquisitionPriceResolver::class)->display($f['affiliate']->fresh(), $f['plan']->fresh()))->toBe('595.00');
});
