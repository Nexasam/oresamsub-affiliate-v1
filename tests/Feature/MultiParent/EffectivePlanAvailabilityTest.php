<?php

use App\Http\Services\DataPlansService;
use App\Http\Services\CustomerPlansPricingService;
use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\AffiliateProductPlanCategory;
use App\Models\AffiliateProcessingProfile;
use App\Models\AffiliateServiceProfitCap;
use App\Models\AffiliateUserPlan;
use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\Network;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Models\ProductPlanParentPrice;
use App\Models\ProviderConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function effectiveAvailabilityFixture(string $suffix = 'one'): array
{
    $parent = ParentBusiness::create(['name' => "Availability {$suffix}", 'slug' => "availability-{$suffix}"]);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create([
        'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id,
        'name' => "Child {$suffix}", 'slug' => "availability-child-{$suffix}", 'affiliate_plan_id' => 1,
        'ip_address' => "127.70.0.{$parent->id}", 'contact_phone' => '08070000001',
        'contact_email' => "availability-{$suffix}@example.test", 'parent_key' => "availability-key-{$suffix}",
        'parent_email' => "availability-parent-{$suffix}@example.test",
    ]);
    $product = Product::create(['api_id' => "availability-data-{$suffix}", 'product_name' => 'Data', 'slug' => 'data']);
    $category = ProductPlanCategory::create(['api_id' => "availability-category-{$suffix}", 'product_id' => $product->id, 'product_plan_category_name' => 'MTN SME']);
    $plan = ProductPlan::create([
        'parent_business_id' => $parent->id, 'product_plan_category_id' => $category->id,
        'product_plan_name' => 'MTN 1GB', 'cost_price' => 400, 'visibility' => 1, 'affiliate_visibility' => 1, 'public_visibility' => 1,
    ]);
    ProductPlanParentPrice::create(['parent_business_id' => $parent->id, 'product_plan_id' => $plan->id, 'parent_reseller_level_id' => $level->id, 'selling_price' => 450]);
    $adapter = ProviderConnection::create(['name' => "Adapter {$suffix}", 'slug' => "availability-adapter-{$suffix}", 'adapter' => 'configurable_http', 'capabilities' => ['services' => ['data']], 'status' => 'active']);
    $connection = ParentProviderConnection::create(['parent_business_id' => $parent->id, 'provider_connection_id' => $adapter->id, 'name' => 'Primary', 'status' => 'active', 'approval_status' => 'approved']);
    $route = $plan->providerRoutes()->create(['parent_business_id' => $parent->id, 'parent_provider_connection_id' => $connection->id, 'provider_plan_id' => 'MTN-1GB', 'priority' => 1, 'active' => true]);
    $affiliateCategory = AffiliateProductPlanCategory::create(['affiliate_id' => $affiliate->id, 'plan_category_id' => $category->id, 'product_id' => $product->id, 'product_plan_category_name' => 'MTN SME']);
    $affiliatePlan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'product_plan_id' => $plan->id, 'plan_category_id' => $affiliateCategory->id,
        'product_plan_name' => 'MTN 1GB', 'visibility' => 1, 'visibility_from_admin' => 1, 'public_visibility' => 1,
        'user_level_1_profit' => 50,
    ]);
    AffiliateProcessingProfile::create(['affiliate_id' => $affiliate->id, 'parent_business_id' => $parent->id, 'management_mode' => 'parent_managed', 'processing_engine' => 'multi_parent', 'status' => 'active']);
    AffiliateServiceProfitCap::create(['parent_business_id' => $parent->id, 'affiliate_id' => $affiliate->id, 'product_id' => $product->id, 'customer_level' => 1, 'calculation_type' => 'flat', 'max_value' => 100]);

    return compact('parent', 'level', 'affiliate', 'product', 'category', 'plan', 'route', 'affiliatePlan');
}

it('exposes one effective availability contract without mutating affiliate preferences', function (string $disabledPart, string $reason) {
    $f = effectiveAvailabilityFixture($disabledPart);

    match ($disabledPart) {
        'parent' => $f['plan']->update(['visibility' => 0]),
        'affiliates' => $f['plan']->update(['affiliate_visibility' => 0]),
        'affiliate' => $f['affiliatePlan']->update(['visibility' => 0]),
        'admin' => $f['affiliatePlan']->update(['visibility_from_admin' => 0]),
        'route' => $f['route']->update(['active' => false]),
    };

    $state = $f['affiliatePlan']->fresh()->availabilityState();

    expect($state['available'])->toBeFalse()
        ->and($state['reason'])->toBe($reason)
        ->and(AffiliateProductPlan::withoutGlobalScope('affiliate')->customerAvailable()->whereKey($f['affiliatePlan']->id)->exists())->toBeFalse()
        ->and((int) $f['affiliatePlan']->fresh()->visibility)->toBe($disabledPart === 'affiliate' ? 0 : 1);
})->with([
    ['parent', 'parent_disabled'],
    ['affiliates', 'parent_hidden_from_affiliates'],
    ['affiliate', 'affiliate_disabled'],
    ['admin', 'platform_disabled'],
    ['route', 'route_inactive'],
]);

it('returns only effectively available plans to a customer catalogue', function () {
    $f = effectiveAvailabilityFixture('catalogue');
    $customerPlan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create(['affiliate_id' => $f['affiliate']->id, 'user_plan_name' => 'Basic', 'plan_level' => 1, 'visibility' => 1]);
    $customer = User::factory()->create(['affiliate_id' => $f['affiliate']->id, 'user_plan_id' => $customerPlan->id]);

    $payload = ['user' => $customer, 'network_id' => null, 'product_id' => $f['product']->id, 'amount' => 0];
    expect(app(DataPlansService::class)->fetch_user_data_plans($payload)['plans'][0]['product_plan_id'])->toBe($f['affiliatePlan']->id);

    $f['plan']->update(['visibility' => 0]);
    expect(app(DataPlansService::class)->fetch_user_data_plans($payload)['plans'][0]['product_plan_id'])->toBeNull();
});

it('keeps approved MTN plans visible when another MTN plan uses a pending connection', function () {
    $f = effectiveAvailabilityFixture('mixed-mtn-routes');
    $customerPlan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $f['affiliate']->id,
        'user_plan_name' => 'Basic',
        'plan_level' => 1,
        'visibility' => 1,
    ]);
    $customer = User::factory()->create([
        'affiliate_id' => $f['affiliate']->id,
        'user_plan_id' => $customerPlan->id,
    ]);

    $pendingProvider = ProviderConnection::create([
        'name' => 'Gongoz',
        'slug' => 'gongoz-mixed-mtn-routes',
        'adapter' => 'configurable_http',
        'capabilities' => ['services' => ['data']],
        'status' => 'active',
    ]);
    $pendingConnection = ParentProviderConnection::create([
        'parent_business_id' => $f['parent']->id,
        'provider_connection_id' => $pendingProvider->id,
        'name' => 'Gongoz pending',
        'status' => 'active',
        'approval_status' => 'pending',
    ]);
    $pendingPlan = ProductPlan::create([
        'parent_business_id' => $f['parent']->id,
        'product_plan_category_id' => $f['category']->id,
        'product_plan_name' => 'MTN 2GB Gongoz',
        'cost_price' => 800,
        'visibility' => 1,
        'affiliate_visibility' => 1,
        'public_visibility' => 1,
    ]);
    $pendingPlan->providerRoutes()->create([
        'parent_business_id' => $f['parent']->id,
        'parent_provider_connection_id' => $pendingConnection->id,
        'provider_plan_id' => 'GONGOZ-MTN-2GB',
        'priority' => 1,
        'active' => true,
    ]);
    AffiliateProductPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $f['affiliate']->id,
        'product_plan_id' => $pendingPlan->id,
        'plan_category_id' => AffiliateProductPlanCategory::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $f['affiliate']->id)
            ->where('plan_category_id', $f['category']->id)
            ->value('id'),
        'product_plan_name' => 'MTN 2GB Gongoz',
        'visibility' => 1,
        'visibility_from_admin' => 1,
        'public_visibility' => 1,
        'user_level_1_profit' => 50,
    ]);

    $plans = app(DataPlansService::class)->fetch_user_data_plans([
        'user' => $customer,
        'network_id' => null,
        'product_id' => $f['product']->id,
        'amount' => 0,
    ])['plans'];

    expect($plans)->toHaveCount(1)
        ->and($plans[0]['product_plan_id'])->toBe($f['affiliatePlan']->id)
        ->and($plans[0]['product_plan_name'])->toBe('MTN 1GB');
});

it('finds MTN plans from the canonical category when the inherited network field is stale', function () {
    $f = effectiveAvailabilityFixture('stale-mtn-network');
    $mtn = Network::create(['api_id' => 'mtn-stale-network', 'network_name' => 'MTN']);
    $f['category']->update(['network_id' => $mtn->id]);

    $customerPlan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $f['affiliate']->id,
        'user_plan_name' => 'Basic',
        'plan_level' => 1,
        'visibility' => 1,
    ]);
    $customer = User::factory()->create([
        'affiliate_id' => $f['affiliate']->id,
        'user_plan_id' => $customerPlan->id,
    ]);

    $plans = app(DataPlansService::class)->fetch_user_data_plans([
        'user' => $customer,
        'network_id' => $mtn->id,
        'product_id' => $f['product']->id,
        'amount' => 0,
    ])['plans'];

    expect($plans[0]['product_plan_id'])->toBe($f['affiliatePlan']->id);
});

it('finds MTN plans even when the inherited category row is missing', function () {
    $f = effectiveAvailabilityFixture('missing-mtn-category');
    $mtn = Network::create(['api_id' => 'mtn-missing-category', 'network_name' => 'MTN']);
    $f['category']->update(['network_id' => $mtn->id]);
    AffiliateProductPlanCategory::withoutGlobalScope('affiliate')
        ->where('affiliate_id', $f['affiliate']->id)
        ->delete();

    $customerPlan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $f['affiliate']->id,
        'user_plan_name' => 'Basic',
        'plan_level' => 1,
        'visibility' => 1,
    ]);
    $customer = User::factory()->create([
        'affiliate_id' => $f['affiliate']->id,
        'user_plan_id' => $customerPlan->id,
    ]);

    $plans = app(DataPlansService::class)->fetch_user_data_plans([
        'user' => $customer,
        'network_id' => $mtn->id,
        'product_id' => $f['product']->id,
        'amount' => 0,
    ])['plans'];

    expect($plans[0]['product_plan_id'])->toBe($f['affiliatePlan']->id);
});

it('finds MTN plans when duplicate network rows use different ids', function () {
    $f = effectiveAvailabilityFixture('duplicate-mtn-network');
    $categoryMtn = Network::create(['api_id' => 'mtn-category-row', 'network_name' => 'MTN']);
    $buttonMtn = Network::create(['api_id' => 'mtn-button-row', 'network_name' => 'MTN']);
    $f['category']->update(['network_id' => $categoryMtn->id]);

    $customerPlan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $f['affiliate']->id,
        'user_plan_name' => 'Basic',
        'plan_level' => 1,
        'visibility' => 1,
    ]);
    $customer = User::factory()->create([
        'affiliate_id' => $f['affiliate']->id,
        'user_plan_id' => $customerPlan->id,
    ]);

    $plans = app(DataPlansService::class)->fetch_user_data_plans([
        'user' => $customer,
        'network_id' => $buttonMtn->id,
        'product_id' => $f['product']->id,
        'amount' => 0,
    ])['plans'];

    expect($plans[0]['product_plan_id'])->toBe($f['affiliatePlan']->id);
});

it('keeps valid MTN plans when another available MTN plan has invalid pricing', function () {
    $f = effectiveAvailabilityFixture('mixed-mtn-pricing');
    $customerPlan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $f['affiliate']->id,
        'user_plan_name' => 'Basic',
        'plan_level' => 1,
        'visibility' => 1,
    ]);
    $customer = User::factory()->create([
        'affiliate_id' => $f['affiliate']->id,
        'user_plan_id' => $customerPlan->id,
    ]);

    $invalidPlan = $f['plan']->replicate();
    $invalidPlan->product_plan_name = 'MTN invalid pricing';
    $invalidPlan->save();
    $invalidPlan->providerRoutes()->create([
        'parent_business_id' => $f['parent']->id,
        'parent_provider_connection_id' => $f['route']->parent_provider_connection_id,
        'provider_plan_id' => 'INVALID-PRICE',
        'priority' => 1,
        'active' => true,
    ]);
    AffiliateProductPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $f['affiliate']->id,
        'product_plan_id' => $invalidPlan->id,
        'product_plan_name' => 'MTN invalid pricing',
        'visibility' => 1,
        'visibility_from_admin' => 1,
        'public_visibility' => 1,
        'user_level_1_profit' => 50,
    ]);
    session(['affiliate' => $f['affiliate']]);

    $payload = ['user' => $customer, 'network_id' => null, 'product_id' => $f['product']->id, 'amount' => 0];
    $dataPlans = app(DataPlansService::class)->fetch_user_data_plans($payload)['plans'];
    $pricingPlans = app(CustomerPlansPricingService::class)->fetch_plans_with_pricing(['user' => $customer])['plans'];

    expect($dataPlans)->toHaveCount(1)
        ->and($dataPlans[0]['product_plan_id'])->toBe($f['affiliatePlan']->id)
        ->and($pricingPlans)->toHaveCount(1)
        ->and($pricingPlans[0]['product_plan_id'])->toBe($f['affiliatePlan']->id);
});

it('keeps an affiliate local visibility preference when plans are synchronized', function () {
    $f = effectiveAvailabilityFixture('sync');
    $f['affiliatePlan']->update(['visibility' => 0, 'public_visibility' => 0]);
    $adminRole = \App\Models\Role::create(['role_name' => 'Admin']);
    $admin = User::factory()->create(['affiliate_id' => $f['affiliate']->id, 'role_id' => $adminRole->id, 'user_plan_id' => null, 'email_verified_at' => now()]);

    $this->actingAs($admin)->withSession(['affiliate' => $f['affiliate']])
        ->postJson('/admin/affiliate/product-plans/sync')->assertOk();

    expect((int) $f['affiliatePlan']->fresh()->visibility)->toBe(0)
        ->and((int) $f['affiliatePlan']->fresh()->public_visibility)->toBe(0);
});

it('shows a parent-disabled plan to the affiliate admin and prevents local reactivation', function () {
    $f = effectiveAvailabilityFixture('admin-state');
    $f['affiliatePlan']->update(['visibility' => 0]);
    $f['plan']->update(['visibility' => 0]);
    $adminRole = \App\Models\Role::create(['role_name' => 'Admin']);
    $admin = User::factory()->create(['affiliate_id' => $f['affiliate']->id, 'role_id' => $adminRole->id, 'user_plan_id' => null, 'email_verified_at' => now()]);

    $this->actingAs($admin)->withSession(['affiliate' => $f['affiliate']])
        ->getJson('/admin/product_plans/fetch_product_plans')
        ->assertOk()
        ->assertJsonPath('data.0.parent_availability', 'Disabled by parent')
        ->assertJsonPath('data.0.affiliate_toggle_enabled', false);

    $this->actingAs($admin)->withSession(['affiliate' => $f['affiliate']])
        ->withHeader('Accept', 'application/json')
        ->get('/admin/toggle_product_visibility?productPlanId='.$f['plan']->id.'&token=test')
        ->assertStatus(422);

    expect((int) $f['affiliatePlan']->fresh()->visibility)->toBe(0);
});
