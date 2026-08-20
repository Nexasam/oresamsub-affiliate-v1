<?php

use App\Models\Affiliate;
use App\Models\AffiliateProcessingProfile;
use App\Models\AffiliateProductPlan;
use App\Models\Network;
use App\Models\ParentBusiness;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Models\ProviderRoutingRollout;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Pricing\MultiParentPricingResolver;
use App\Services\Providers\ParentManagedPurchaseOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

function dataControllerRoutingFixture(): array
{
    $parent = ParentBusiness::create(['name' => 'Data Parent', 'slug' => 'data-parent']);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create([
        'parent_business_id' => $parent->id,
        'parent_reseller_level_id' => $level->id,
        'name' => 'Data Affiliate',
        'slug' => 'data-affiliate',
        'affiliate_plan_id' => 1,
        'ip_address' => '127.9.0.2',
        'domain_url' => 'data.test',
        'contact_phone' => '08050000092',
        'contact_email' => 'data@example.test',
        'parent_key' => 'data-key',
        'parent_email' => 'data-parent@example.test',
        'activation_status' => 1,
    ]);
    $network = Network::create(['api_id' => 'mtn-data', 'network_name' => 'MTN']);
    $product = Product::create(['api_id' => 'data-product', 'product_name' => 'Data', 'slug' => 'data']);
    $category = ProductPlanCategory::create([
        'api_id' => 'data-category',
        'product_id' => $product->id,
        'network_id' => $network->id,
        'product_plan_category_name' => 'MTN SME',
    ]);
    $plan = ProductPlan::create([
        'parent_business_id' => $parent->id,
        'product_plan_category_id' => $category->id,
        'product_plan_name' => 'MTN 1GB',
        'api_id' => 'MTN-1GB',
        'cost_price' => '500.00',
        'cost_price_1' => '500.00',
        'visibility' => 1,
        'affiliate_visibility' => 1,
    ]);
    $affiliatePlan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id,
        'product_plan_id' => $plan->id,
        'product_plan_name' => 'MTN 1GB',
        'visibility' => 1,
        'visibility_from_admin' => 1,
        'user_level_1_profit' => '50.00',
    ]);
    AffiliateProcessingProfile::create([
        'affiliate_id' => $affiliate->id,
        'parent_business_id' => $parent->id,
        'management_mode' => 'parent_managed',
        'processing_engine' => 'multi_parent',
        'status' => 'active',
    ]);
    ProviderRoutingRollout::create([
        'parent_business_id' => $parent->id,
        'scope_type' => 'affiliate',
        'scope_id' => $affiliate->id,
        'service' => 'data',
        'enabled' => true,
    ]);
    $userPlanId = DB::table('affiliate_user_plans')->insertGetId([
        'affiliate_id' => $affiliate->id,
        'user_plan_name' => 'Basic',
        'plan_level' => 1,
        'is_default' => 1,
        'visibility' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $customer = User::factory()->create([
        'affiliate_id' => $affiliate->id,
        'user_plan_id' => $userPlanId,
        'main_wallet' => '1000.00',
        'pin' => '1234',
        'email_verified_at' => now(),
    ]);

    return compact('parent', 'affiliate', 'network', 'affiliatePlan', 'customer');
}

function postDataPurchase($test, array $fixture)
{
    return $test->withoutMiddleware()
        ->actingAs($fixture['customer'])
        ->withSession(['affiliate' => $fixture['affiliate']])
        ->postJson('/user/data/store2', [
            'network_id' => $fixture['network']->id,
            'phone_number' => '08030000000',
            'product_plan_id' => $fixture['affiliatePlan']->id,
            'pin' => '1234',
            'wallet_category' => 'main_wallet',
            'validatephonenetwork' => 0,
        ]);
}

it('does not fall back to legacy data when the parent-managed feature is disabled', function () {
    $f = dataControllerRoutingFixture();
    config()->set('parent_businesses.features.parent_managed_purchases', false);
    config()->set('parent_businesses.features.provider_routing', true);
    $this->mock(MultiParentPricingResolver::class, fn (MockInterface $mock) => $mock->shouldReceive('resolve')->once()->andReturn(['customer_selling_price' => '550.00']));
    $this->mock(ParentManagedPurchaseOrchestrator::class, fn (MockInterface $mock) => $mock->shouldNotReceive('purchase'));

    postDataPurchase($this, $f)->assertOk()
        ->assertJsonPath('status', -1)
        ->assertJsonPath('message', 'Parent-managed Data purchasing is not enabled.');

    expect($f['customer']->fresh()->main_wallet)->toBe('1000.00')
        ->and(Transaction::withoutGlobalScope('affiliate')->doesntExist())->toBeTrue();
});

it('does not fall back to legacy data when rollout is disabled for a multi-parent affiliate', function () {
    $f = dataControllerRoutingFixture();
    config()->set('parent_businesses.features.parent_managed_purchases', true);
    config()->set('parent_businesses.features.provider_routing', true);
    ProviderRoutingRollout::query()->delete();
    $this->mock(MultiParentPricingResolver::class, fn (MockInterface $mock) => $mock->shouldReceive('resolve')->once()->andReturn(['customer_selling_price' => '550.00']));
    $this->mock(ParentManagedPurchaseOrchestrator::class, fn (MockInterface $mock) => $mock->shouldNotReceive('purchase'));

    postDataPurchase($this, $f)->assertOk()
        ->assertJsonPath('status', -1)
        ->assertJsonPath('message', 'Data purchasing is not enabled for this affiliate.');

    expect($f['customer']->fresh()->main_wallet)->toBe('1000.00')
        ->and(Transaction::withoutGlobalScope('affiliate')->doesntExist())->toBeTrue();
});

it('retains legacy data processing for an explicit legacy oresamsub profile', function () {
    $f = dataControllerRoutingFixture();
    config()->set('parent_businesses.features.parent_managed_purchases', false);
    config()->set('parent_businesses.features.provider_routing', false);
    $f['affiliate']->processingProfile()->update([
        'management_mode' => 'affiliate_managed',
        'processing_engine' => 'legacy_oresamsub',
    ]);
    $f['customer']->update(['main_wallet' => '0.00']);
    $this->mock(ParentManagedPurchaseOrchestrator::class, fn (MockInterface $mock) => $mock->shouldNotReceive('purchase'));
    $transactionLevel = DB::transactionLevel();

    postDataPurchase($this, $f)->assertOk()
        ->assertJsonPath('status', -1)
        ->assertJsonPath('message', 'Insufficient wallet balance');

    expect(DB::transactionLevel())->toBe($transactionLevel);
});
