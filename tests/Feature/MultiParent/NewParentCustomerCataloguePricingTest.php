<?php

use App\Http\Services\DataPlansService;
use App\Models\AffiliateProcessingProfile;
use App\Models\AffiliateServiceProfitCap;
use App\Models\AffiliateUserPlan;
use App\Models\ParentDefaultProfitRule;
use App\Models\ProductPlanParentPrice;
use App\Models\User;

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
