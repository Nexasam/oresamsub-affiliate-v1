<?php

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\AffiliateProductPlanCategory;
use App\Models\AffiliateUserPlan;
use App\Models\ParentProviderConnection;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Models\ProviderConnection;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function planHealthFixture(string $suffix = 'main'): array
{
    $parent = ParentBusiness::create(['name' => "Health {$suffix}", 'slug' => "health-{$suffix}"]);
    $admin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => "health-{$suffix}@example.test", 'password' => 'password123', 'active' => true]);
    $level = $parent->resellerLevels()->create(['name' => 'Level 1', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create([
        'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id,
        'name' => "Health Child {$suffix}", 'slug' => "health-child-{$suffix}", 'affiliate_plan_id' => 1,
        'ip_address' => "127.80.0.{$parent->id}", 'contact_phone' => '08080000001',
        'contact_email' => "health-child-{$suffix}@example.test", 'parent_key' => "health-key-{$suffix}",
        'parent_email' => "health-parent-{$suffix}@example.test",
    ]);
    $product = Product::create(['api_id' => "health-product-{$suffix}", 'product_name' => 'Data', 'slug' => 'data']);
    $category = ProductPlanCategory::create(['api_id' => "health-category-{$suffix}", 'product_id' => $product->id, 'product_plan_category_name' => 'MTN SME']);
    $plan = ProductPlan::create(['parent_business_id' => $parent->id, 'product_plan_category_id' => $category->id, 'product_plan_name' => 'MTN 1GB Health', 'cost_price' => 400, 'visibility' => 1, 'affiliate_visibility' => 1, 'public_visibility' => 1]);
    $adapter = ProviderConnection::create(['name' => 'Health Provider', 'slug' => "health-provider-{$suffix}", 'adapter' => 'configurable_http', 'capabilities' => ['services' => ['data']], 'status' => 'active']);
    $connection = ParentProviderConnection::create(['parent_business_id' => $parent->id, 'provider_connection_id' => $adapter->id, 'name' => 'Health Primary', 'base_url' => 'https://provider.example.test/api/v2', 'status' => 'active', 'approval_status' => 'approved']);
    $route = $plan->providerRoutes()->create(['parent_business_id' => $parent->id, 'parent_provider_connection_id' => $connection->id, 'provider_plan_id' => 'MTN-1GB', 'priority' => 1, 'active' => true]);
    $affiliateCategory = AffiliateProductPlanCategory::create(['affiliate_id' => $affiliate->id, 'plan_category_id' => $category->id, 'product_id' => $product->id, 'product_plan_category_name' => 'MTN SME']);
    $affiliatePlan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create(['affiliate_id' => $affiliate->id, 'product_plan_id' => $plan->id, 'plan_category_id' => $affiliateCategory->id, 'product_plan_name' => 'MTN 1GB Health', 'visibility' => 1, 'visibility_from_admin' => 1, 'public_visibility' => 1]);
    $role = Role::create(['role_name' => "Health User {$suffix}"]);
    $customerPlan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create(['affiliate_id' => $affiliate->id, 'user_plan_name' => 'Basic', 'plan_level' => 1]);
    $customer = User::factory()->create(['affiliate_id' => $affiliate->id, 'role_id' => $role->id, 'user_plan_id' => $customerPlan->id]);

    return compact('parent', 'admin', 'affiliate', 'plan', 'connection', 'route', 'affiliatePlan', 'customer');
}

it('groups recent failures by plan and connection on the owning parent dashboard', function () {
    $f = planHealthFixture();
    foreach (['HEALTH-FAIL-1', 'HEALTH-FAIL-2', 'HEALTH-FAIL-3'] as $reference) {
        Transaction::withoutGlobalScope('affiliate')->create([
            'parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id,
            'user_id' => $f['customer']->id, 'api_id' => 'MTN-1GB', 'affiliate_product_plan_id' => $f['affiliatePlan']->id,
            'parent_provider_connection_id' => $f['connection']->id, 'product_plan_provider_route_id' => $f['route']->id,
            'wallet_category' => 'main_wallet', 'balance_before' => 1000, 'balance_after' => 1000,
            'description' => 'Failed purchase', 'txn_reference' => $reference, 'transaction_category' => 'data',
            'amount' => 500, 'status' => 2, 'routing_status' => 'failed', 'admin_screen_message' => 'Provider wallet is low.',
        ]);
    }

    $this->actingAs($f['admin'], 'parent_admin')->get('/parent-admin')
        ->assertOk()->assertSee('Plan health alerts')->assertSee('MTN 1GB Health')
        ->assertSee('3 recent failures')->assertSee('Provider wallet is low.')
        ->assertSee('Health Primary')->assertSee($f['affiliate']->name)
        ->assertSee('https://provider.example.test')->assertSee('HEALTH-FAIL-3')
        ->assertSee('Switch provider')->assertSee('MTN-1GB');
});

it('shows only threshold-qualified unresolved or failed route incidents', function () {
    $f = planHealthFixture('threshold');
    foreach (range(1, 4) as $index) {
        Transaction::withoutGlobalScope('affiliate')->create([
            'parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id,
            'user_id' => $f['customer']->id, 'api_id' => 'MTN-1GB', 'affiliate_product_plan_id' => $f['affiliatePlan']->id,
            'parent_provider_connection_id' => $f['connection']->id, 'product_plan_provider_route_id' => $f['route']->id,
            'wallet_category' => 'main_wallet', 'balance_before' => 1000, 'balance_after' => 1000,
            'description' => 'Old failed purchase', 'txn_reference' => "HEALTH-OLD-{$index}", 'transaction_category' => 'data',
            'amount' => 500, 'status' => 2, 'routing_status' => 'failed', 'admin_screen_message' => 'Old provider issue.',
            'created_at' => now()->subHours(2), 'updated_at' => now()->subHours(2),
        ]);
    }

    $this->actingAs($f['admin'], 'parent_admin')->get('/parent-admin')
        ->assertOk()->assertSee('No recent plan failures need attention.');

    Transaction::withoutGlobalScope('affiliate')->create([
        'parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id,
        'user_id' => $f['customer']->id, 'api_id' => 'MTN-1GB', 'affiliate_product_plan_id' => $f['affiliatePlan']->id,
        'parent_provider_connection_id' => $f['connection']->id, 'product_plan_provider_route_id' => $f['route']->id,
        'wallet_category' => 'main_wallet', 'balance_before' => 1000, 'balance_after' => 500,
        'description' => 'Recovered purchase', 'txn_reference' => 'HEALTH-RECOVERED', 'transaction_category' => 'data',
        'amount' => 500, 'status' => 1, 'routing_status' => 'successful', 'admin_screen_message' => 'Manually confirmed successful.',
    ]);

    $this->actingAs($f['admin'], 'parent_admin')->get('/parent-admin')
        ->assertOk()->assertSee('No recent plan failures need attention.');
});

it('disables a failing parent plan without changing affiliate local preferences', function () {
    $f = planHealthFixture('disable');

    $this->actingAs($f['admin'], 'parent_admin')
        ->patch(route('parent-admin.product-plans.disable', $f['plan']))
        ->assertRedirect(route('parent-admin.dashboard'));

    expect((bool) $f['plan']->fresh()->visibility)->toBeFalse()
        ->and((bool) $f['plan']->fresh()->affiliate_visibility)->toBeFalse()
        ->and((bool) $f['route']->fresh()->active)->toBeFalse()
        ->and((bool) $f['affiliatePlan']->fresh()->visibility)->toBeTrue();
});

it('creates one deduplicated parent health notification for a threshold incident', function () {
    $f = planHealthFixture('notification');
    foreach (range(1, 3) as $index) {
        Transaction::withoutGlobalScope('affiliate')->create([
            'parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id,
            'user_id' => $f['customer']->id, 'api_id' => 'MTN-1GB', 'affiliate_product_plan_id' => $f['affiliatePlan']->id,
            'parent_provider_connection_id' => $f['connection']->id, 'product_plan_provider_route_id' => $f['route']->id,
            'wallet_category' => 'main_wallet', 'balance_before' => 1000, 'balance_after' => 1000,
            'description' => 'Failed purchase', 'txn_reference' => "HEALTH-NOTIFY-{$index}", 'transaction_category' => 'data',
            'amount' => 500, 'status' => 2, 'routing_status' => 'failed', 'admin_screen_message' => 'Provider is unhealthy.',
        ]);
    }

    $this->actingAs($f['admin'], 'parent_admin')->get('/parent-admin')->assertOk();
    $this->actingAs($f['admin'], 'parent_admin')->get('/parent-admin')->assertOk();

    expect($f['admin']->notifications()->count())->toBe(1)
        ->and($f['admin']->notifications()->first()->data['failure_count'])->toBe(3);
});
