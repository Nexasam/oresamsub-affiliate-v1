<?php

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\ParentBusiness;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;

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
