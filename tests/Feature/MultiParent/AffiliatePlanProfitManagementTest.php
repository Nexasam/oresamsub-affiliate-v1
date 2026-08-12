<?php

use App\Models\AffiliateServiceProfitCap;
use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\ParentBusiness;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Models\ProductPlanParentPrice;
use App\Services\Pricing\AffiliatePlanProfitService;
use Illuminate\Validation\ValidationException;

function affiliateProfitFixture(string $suffix): array
{
    $parent = ParentBusiness::create(['name' => "Profit {$suffix}", 'slug' => "profit-{$suffix}"]);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $product = Product::create(['api_id' => "profit-product-{$suffix}", 'product_name' => 'Data', 'slug' => "data-profit-{$suffix}"]);
    $category = ProductPlanCategory::create(['api_id' => "profit-category-{$suffix}", 'product_id' => $product->id, 'product_plan_category_name' => "Category {$suffix}"]);
    $plan = ProductPlan::create(['parent_business_id' => $parent->id, 'product_plan_category_id' => $category->id, 'product_plan_name' => "Plan {$suffix}", 'cost_price' => 100, 'profit_category' => 'flat']);
    $affiliate = Affiliate::create(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id, 'name' => "Affiliate {$suffix}", 'slug' => "profit-affiliate-{$suffix}", 'affiliate_plan_id' => 1, 'ip_address' => "profit-{$suffix}", 'contact_phone' => "080{$suffix}", 'contact_email' => "profit-{$suffix}@example.test", 'parent_key' => "key-{$suffix}", 'parent_email' => "parent-{$suffix}@example.test"]);
    $affiliatePlan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create(['affiliate_id' => $affiliate->id, 'product_plan_id' => $plan->id, 'product_plan_name' => $plan->product_plan_name, 'visibility' => 1, 'user_level_1_profit' => 10]);

    return compact('parent', 'level', 'product', 'plan', 'affiliate', 'affiliatePlan');
}

it('updates all six customer margins within normalized parent caps', function () {
    $f = affiliateProfitFixture('editor1');
    foreach (range(1, 6) as $level) {
        AffiliateServiceProfitCap::create(['parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id, 'product_id' => $f['product']->id, 'customer_level' => $level, 'calculation_type' => 'flat', 'max_value' => 70]);
    }

    app(AffiliatePlanProfitService::class)->update($f['affiliate'], $f['plan']->id, array_fill_keys(range(1, 6), 60));

    expect((float) $f['affiliatePlan']->fresh()->user_level_1_profit)->toBe(60.0)
        ->and((float) $f['affiliatePlan']->fresh()->user_level_6_profit)->toBe(60.0);
});

it('uses the stricter plan level max profit override', function () {
    $f = affiliateProfitFixture('editor2');
    foreach (range(1, 6) as $level) {
        AffiliateServiceProfitCap::create(['parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id, 'product_id' => $f['product']->id, 'customer_level' => $level, 'calculation_type' => 'flat', 'max_value' => 70]);
    }
    ProductPlanParentPrice::create(['parent_business_id' => $f['parent']->id, 'product_plan_id' => $f['plan']->id, 'parent_reseller_level_id' => $f['level']->id, 'selling_price' => 120, 'max_profit' => 50]);

    expect(fn () => app(AffiliatePlanProfitService::class)->update($f['affiliate'], $f['plan']->id, [1 => 60, 2 => 40, 3 => 40, 4 => 40, 5 => 40, 6 => 40]))
        ->toThrow(ValidationException::class);
});

it('rejects a plan owned by another affiliate or parent', function () {
    $first = affiliateProfitFixture('editor3');
    $other = affiliateProfitFixture('editor4');

    expect(fn () => app(AffiliatePlanProfitService::class)->update($first['affiliate'], $other['plan']->id, array_fill_keys(range(1, 6), 1)))
        ->toThrow(ValidationException::class);
});
