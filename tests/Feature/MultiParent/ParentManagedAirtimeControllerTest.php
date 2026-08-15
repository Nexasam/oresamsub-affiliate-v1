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
use App\Services\Providers\ParentManagedPurchaseOrchestrator;
use App\Services\Providers\ParentPurchaseExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

function airtimeControllerFixture(): array
{
    $parent = ParentBusiness::create(['name' => 'Airtime Parent', 'slug' => 'airtime-parent']);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create([
        'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id,
        'name' => 'Airtime Affiliate', 'slug' => 'airtime-affiliate', 'affiliate_plan_id' => 1,
        'ip_address' => '127.9.0.1', 'domain_url' => 'airtime.test', 'contact_phone' => '08050000091',
        'contact_email' => 'airtime@example.test', 'parent_key' => 'airtime-key',
        'parent_email' => 'airtime-parent@example.test', 'activation_status' => 1,
    ]);
    $network = Network::create(['api_id' => 'mtn-airtime', 'network_name' => 'MTN']);
    $product = Product::create(['api_id' => 'airtime-product', 'product_name' => 'Airtime', 'slug' => 'airtime']);
    $category = ProductPlanCategory::create(['api_id' => 'airtime-category', 'product_id' => $product->id, 'network_id' => $network->id, 'product_plan_category_name' => 'MTN VTU']);
    $plan = ProductPlan::create(['parent_business_id' => $parent->id, 'product_plan_category_id' => $category->id, 'product_plan_name' => 'MTN Airtime', 'api_id' => 'MTN-AIRTIME', 'cost_price' => '950.00']);
    $affiliatePlan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create(['affiliate_id' => $affiliate->id, 'product_plan_id' => $plan->id, 'product_plan_name' => 'MTN Airtime', 'visibility' => 1, 'visibility_from_admin' => 1, 'user_level_1_profit' => '1']);
    AffiliateProcessingProfile::create(['affiliate_id' => $affiliate->id, 'parent_business_id' => $parent->id, 'management_mode' => 'parent_managed', 'processing_engine' => 'multi_parent', 'status' => 'active']);
    ProviderRoutingRollout::create(['parent_business_id' => $parent->id, 'scope_type' => 'affiliate', 'scope_id' => $affiliate->id, 'service' => 'airtime', 'enabled' => true]);
    $userPlanId = DB::table('affiliate_user_plans')->insertGetId(['affiliate_id' => $affiliate->id, 'user_plan_name' => 'Basic', 'plan_level' => 1, 'is_default' => 1, 'visibility' => 1, 'created_at' => now(), 'updated_at' => now()]);
    $customer = User::factory()->create(['affiliate_id' => $affiliate->id, 'user_plan_id' => $userPlanId, 'main_wallet' => '1500.00', 'pin' => '1234', 'email_verified_at' => now()]);

    return compact('parent', 'affiliate', 'network', 'affiliatePlan', 'customer');
}

it('routes one eligible airtime number through the parent-managed orchestrator and exits before legacy deduction', function () {
    $f = airtimeControllerFixture();
    config()->set('parent_businesses.features.parent_managed_purchases', true);
    config()->set('parent_businesses.features.provider_routing', true);
    $transaction = new Transaction(['status' => 1, 'txn_reference' => 'AIRTIME-CONTROLLED-1', 'user_screen_message' => 'Airtime delivered']);

    $this->mock(ParentManagedPurchaseOrchestrator::class, fn (MockInterface $mock) => $mock->shouldReceive('purchase')->once()->withArgs(function ($customer, $plan, $runtime, $level, $faceAmount) use ($f) {
        return $customer->is($f['customer']) && $plan->is($f['affiliatePlan']) && $runtime['service'] === 'airtime'
            && $runtime['amount'] === '1000' && $runtime['phone_number'] === '08030000000'
            && $runtime['network'] === 'MTN' && $level === 1 && $faceAmount === '1000';
    })->andReturn(['transaction' => $transaction, 'provider_result' => ['successful' => true]]));
    $this->mock(ParentPurchaseExecutor::class, fn (MockInterface $mock) => $mock->shouldNotReceive('execute'));

    $response = $this->withoutMiddleware()->actingAs($f['customer'])->withSession(['affiliate' => $f['affiliate']])->postJson('/user/airtime/store2', [
        'network_id' => $f['network']->id, 'phone_number' => '08030000000', 'product_plan_id' => $f['affiliatePlan']->id,
        'pin' => '1234', 'amount' => 1000, 'validatephonenetwork' => 0, 'wallet_category' => 'main_wallet',
    ]);

    $response->assertOk()->assertJsonPath('status', 1)->assertJsonPath('message', 'Airtime delivered');
    expect($f['customer']->fresh()->main_wallet)->toBe('1500.00')
        ->and(Transaction::withoutGlobalScope('affiliate')->count())->toBe(0);
});

it('rejects multiple numbers on the controlled airtime route before any provider request', function () {
    $f = airtimeControllerFixture();
    config()->set('parent_businesses.features.parent_managed_purchases', true);
    config()->set('parent_businesses.features.provider_routing', true);
    $this->mock(ParentManagedPurchaseOrchestrator::class, fn (MockInterface $mock) => $mock->shouldNotReceive('purchase'));
    $this->mock(ParentPurchaseExecutor::class, fn (MockInterface $mock) => $mock->shouldNotReceive('execute'));

    $this->withoutMiddleware()->actingAs($f['customer'])->withSession(['affiliate' => $f['affiliate']])->postJson('/user/airtime/store2', [
        'network_id' => $f['network']->id, 'phone_number' => '08030000000,08030000001', 'product_plan_id' => $f['affiliatePlan']->id,
        'pin' => '1234', 'amount' => 1000, 'validatephonenetwork' => 0, 'wallet_category' => 'main_wallet',
    ])->assertOk()->assertJsonPath('status', -1)->assertJsonPath('message', 'The controlled parent-managed rollout currently supports one Airtime number from the main wallet per request.');
});

it('falls back to the legacy airtime flow when parent-managed purchases are disabled', function () {
    $f = airtimeControllerFixture();
    config()->set('parent_businesses.features.parent_managed_purchases', false);
    config()->set('parent_businesses.features.provider_routing', true);
    $f['customer']->update(['main_wallet' => '0.00']);

    $this->mock(ParentManagedPurchaseOrchestrator::class, fn (MockInterface $mock) => $mock->shouldNotReceive('purchase'));
    $this->mock(ParentPurchaseExecutor::class, fn (MockInterface $mock) => $mock->shouldNotReceive('execute'));

    $this->withoutMiddleware()->actingAs($f['customer'])->withSession(['affiliate' => $f['affiliate']])->postJson('/user/airtime/store2', [
        'network_id' => $f['network']->id, 'phone_number' => '08030000000', 'product_plan_id' => $f['affiliatePlan']->id,
        'pin' => '1234', 'amount' => 1000, 'validatephonenetwork' => 0, 'wallet_category' => 'main_wallet',
    ])->assertOk()->assertJsonPath('status', -1)->assertJsonPath('message', 'Insufficient wallet balance');
});
