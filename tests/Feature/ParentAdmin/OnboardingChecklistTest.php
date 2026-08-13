<?php

use App\Models\AffiliateProductPlan;
use App\Models\Affiliate;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\AffiliateUserPlan;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Models\Role;
use App\Models\User;
use App\Models\Transaction;
use App\Services\Onboarding\OnboardingChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function checklistParent(string $slug): array
{
    $parent = ParentBusiness::create(['name' => ucfirst($slug), 'slug' => $slug]);
    $admin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => "$slug@example.test", 'password' => 'password123', 'active' => true]);
    $levels = collect([1, 2])->map(fn ($position) => $parent->resellerLevels()->create(['name' => "Level $position", 'position' => $position, 'status' => 'active']));
    return [$parent, $admin, $levels];
}

function checklistAffiliate(string $slug): Affiliate
{
    static $sequence = 40; $sequence++;
    return Affiliate::create(['name' => ucfirst($slug), 'slug' => $slug, 'affiliate_plan_id' => 1, 'ip_address' => "127.0.3.$sequence", 'contact_phone' => "08040000$sequence", 'contact_email' => "$slug@example.test", 'parent_key' => "key-$slug", 'parent_email' => "parent-$slug@example.test"]);
}

function checklistAffiliatePlan(ParentBusiness $parent, Affiliate $affiliate): AffiliateProductPlan
{
    $product = Product::create(['api_id' => 'data-'.$affiliate->id, 'product_name' => 'Data', 'slug' => 'data-'.$affiliate->id]);
    $category = ProductPlanCategory::create(['api_id' => 'cat-'.$affiliate->id, 'product_id' => $product->id, 'product_plan_category_name' => 'MTN']);
    $plan = ProductPlan::create(['parent_business_id' => $parent->id, 'product_plan_name' => '1GB', 'product_plan_category_id' => $category->id, 'cost_price' => 100]);
    return AffiliateProductPlan::withoutGlobalScope('affiliate')->create(['affiliate_id' => $affiliate->id, 'product_plan_id' => $plan->id, 'product_plan_name' => '1GB']);
}

it('counts a successful transaction for the parent even when another affiliate is active in session', function () {
    [$parent, $admin, $levels] = checklistParent('checklist-parent');
    $affiliate = checklistAffiliate('checklist-affiliate');
    $affiliate->update(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $levels[0]->id]);
    $other = checklistAffiliate('checklist-other');
    $affiliatePlan = checklistAffiliatePlan($parent, $affiliate);
    $role = Role::create(['role_name' => 'User']);
    $userPlan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create(['affiliate_id' => $affiliate->id, 'user_plan_name' => 'Basic', 'plan_level' => 1]);
    $user = User::factory()->create(['affiliate_id' => $affiliate->id, 'role_id' => $role->id, 'user_plan_id' => $userPlan->id]);

    Transaction::withoutGlobalScope('affiliate')->create([
        'parent_business_id' => $parent->id, 'affiliate_id' => $affiliate->id,
        'api_id' => 'plan', 'affiliate_product_plan_id' => $affiliatePlan->id, 'user_id' => $user->id,
        'txn_reference' => 'CHECKLIST-SUCCESS', 'status' => 1, 'wallet_category' => 'main_wallet',
        'amount' => 100, 'balance_before' => 200, 'balance_after' => 100, 'description' => 'Success',
    ]);

    $this->withSession(['affiliate' => $other]);
    $checklist = app(OnboardingChecklistService::class)->forParent($parent);

    expect(collect($checklist['steps'])->firstWhere('name', 'Successful test transaction')['complete'])->toBeTrue();
});

it('shows affiliate setup as complete when a parent affiliate has a generated catalogue', function () {
    [$parent, $admin, $levels] = checklistParent('affiliate-setup-parent');
    $affiliate = checklistAffiliate('configured-affiliate');
    $affiliate->update(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $levels[0]->id]);
    checklistAffiliatePlan($parent, $affiliate);

    $checklist = app(OnboardingChecklistService::class)->forParent($parent);

    expect(collect($checklist['steps'])->firstWhere('name', 'Affiliate setup')['complete'])->toBeTrue();
});
