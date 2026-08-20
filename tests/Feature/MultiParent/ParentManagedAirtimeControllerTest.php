<?php

use App\Http\Controllers\AirtimeController;
use App\Models\Affiliate;
use App\Models\AffiliateProcessingProfile;
use App\Models\AffiliateProductPlan;
use App\Models\AffiliateServiceProfitCap;
use App\Models\AffiliateSettlementLedgerEntry;
use App\Models\AffiliateSettlementWallet;
use App\Models\Network;
use App\Models\ParentBusiness;
use App\Models\ParentDefaultProfitRule;
use App\Models\ParentProviderConnection;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Models\ProviderConnection;
use App\Models\ProviderRoutingRollout;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletLog;
use App\Services\Providers\ParentManagedPurchaseOrchestrator;
use App\Services\Providers\ParentPurchaseExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

it('normalizes airtime provider messages when the upstream response omits message', function () {
    $controller = app(AirtimeController::class);
    $method = new ReflectionMethod($controller, 'providerResponseMessage');
    $method->setAccessible(true);

    expect($method->invoke($controller, ['status' => 1], 'user_message', 'Airtime transaction was successful.'))
        ->toBe('Airtime transaction was successful.')
        ->and($method->invoke($controller, ['data' => ['user_message' => 'Delivered']], 'user_message', 'Fallback'))
        ->toBe('Delivered')
        ->and($method->invoke($controller, ['message' => 'Provider accepted request'], 'user_message', 'Fallback'))
        ->toBe('Provider accepted request');
});

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

it('completes airtime from the customer request through provider routing and financial settlement', function () {
    $f = airtimeControllerFixture();
    config()->set('parent_businesses.features.parent_managed_purchases', true);
    config()->set('parent_businesses.features.provider_routing', true);

    $adapter = ProviderConnection::create([
        'name' => 'Airtime HTTP',
        'slug' => 'airtime-http',
        'adapter' => 'configurable_http',
        'capabilities' => ['services' => ['airtime']],
        'status' => 'active',
    ]);
    $connection = ParentProviderConnection::create([
        'parent_business_id' => $f['parent']->id,
        'provider_connection_id' => $adapter->id,
        'name' => 'Airtime Primary',
        'status' => 'active',
        'approval_status' => 'approved',
        'credentials' => ['api_public_key' => 'airtime-test-token'],
        'settings' => [
            'http_method' => 'POST',
            'expected_success_code' => 200,
            'endpoints' => ['airtime' => 'https://provider.example/api/buy-service'],
            'product_configs' => [
                'airtime' => [
                    'request_parameters' => [
                        ['key' => 'service', 'type' => 'literal', 'value' => 'airtime'],
                        ['key' => 'plan_id', 'type' => 'runtime', 'value' => 'plan'],
                        ['key' => 'customer_number', 'type' => 'runtime', 'value' => 'phone_number'],
                        ['key' => 'network_id', 'type' => 'runtime', 'value' => 'network'],
                        ['key' => 'reference', 'type' => 'runtime', 'value' => 'reference'],
                    ],
                    'request_headers' => [
                        ['key' => 'Authorization', 'type' => 'credential', 'value' => 'api_public_key', 'prefix' => 'Bearer'],
                    ],
                    'network_mapping' => ['MTN' => '1'],
                    'success_conditions' => [['key' => 'success', 'value' => 'true']],
                    'success_message_path' => 'message',
                    'failure_message_path' => 'message',
                ],
            ],
        ],
    ]);

    $parentPlan = $f['affiliatePlan']->product_plan;
    $parentPlan->update(['visibility' => 1, 'affiliate_visibility' => 1, 'profit_category' => 'percent']);
    $parentPlan->providerRoutes()->create([
        'parent_business_id' => $f['parent']->id,
        'parent_provider_connection_id' => $connection->id,
        'provider_plan_id' => 'MTN-AIRTIME-VTU',
        'priority' => 1,
        'active' => true,
    ]);
    ParentDefaultProfitRule::create([
        'parent_business_id' => $f['parent']->id,
        'parent_reseller_level_id' => $f['affiliate']->parent_reseller_level_id,
        'product_id' => $parentPlan->product_plan_category->product_id,
        'calculation_type' => 'percent_discount',
        'value' => '3.00',
    ]);
    AffiliateServiceProfitCap::create([
        'parent_business_id' => $f['parent']->id,
        'affiliate_id' => $f['affiliate']->id,
        'product_id' => $parentPlan->product_plan_category->product_id,
        'customer_level' => 1,
        'calculation_type' => 'percent',
        'max_value' => '1.00',
    ]);
    AffiliateSettlementWallet::create([
        'affiliate_id' => $f['affiliate']->id,
        'parent_business_id' => $f['parent']->id,
        'available_balance' => '1500.00',
        'reserved_balance' => '0.00',
        'currency' => 'NGN',
        'status' => 'active',
    ]);

    Http::fake([
        'https://provider.example/api/buy-service' => Http::response([
            'success' => true,
            'message' => 'Transaction processed successfully.',
            'provider_reference' => 'AIRTIME-UPSTREAM-100',
        ], 200),
    ]);

    $response = $this->withoutMiddleware()->actingAs($f['customer'])->withSession(['affiliate' => $f['affiliate']])->postJson('/user/airtime/store2', [
        'network_id' => $f['network']->id,
        'phone_number' => '08030000000',
        'product_plan_id' => $f['affiliatePlan']->id,
        'pin' => '1234',
        'amount' => 1000,
        'validatephonenetwork' => 0,
        'wallet_category' => 'main_wallet',
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 1)
        ->assertJsonPath('message', 'Transaction processed successfully.');

    $reference = $response->json('data.0.reference');
    $transaction = Transaction::withoutGlobalScope('affiliate')->where('txn_reference', $reference)->firstOrFail();
    $wallet = AffiliateSettlementWallet::where('affiliate_id', $f['affiliate']->id)->firstOrFail();

    Http::assertSent(fn ($request) => $request->url() === 'https://provider.example/api/buy-service'
        && $request->method() === 'POST'
        && $request->header('Authorization')[0] === 'Bearer airtime-test-token'
        && $request['service'] === 'airtime'
        && $request['plan_id'] === 'MTN-AIRTIME-VTU'
        && $request['customer_number'] === '08030000000'
        && $request['network_id'] === '1'
        && $request['reference'] === $reference);

    expect($transaction->transaction_category)->toBe('airtime')
        ->and($transaction->status)->toBe('1')
        ->and($transaction->routing_status)->toBe('successful')
        ->and($transaction->provider_reference)->toBe('AIRTIME-UPSTREAM-100')
        ->and($transaction->provider_cost_snapshot)->toBe('950.00')
        ->and($transaction->affiliate_cost_snapshot)->toBe('970.00')
        ->and($transaction->customer_price_snapshot)->toBe('990.00')
        ->and($transaction->parent_profit_snapshot)->toBe('20.00')
        ->and($transaction->affiliate_profit_snapshot)->toBe('20.00')
        ->and($f['customer']->fresh()->main_wallet)->toBe('510.00')
        ->and($wallet->available_balance)->toBe('530.00')
        ->and($wallet->reserved_balance)->toBe('0.00')
        ->and(AffiliateSettlementLedgerEntry::where('reference', $reference.':reserve')->count())->toBe(1)
        ->and(AffiliateSettlementLedgerEntry::where('reference', $reference.':capture')->count())->toBe(1)
        ->and(WalletLog::withoutGlobalScope('affiliate')->where('transaction_id', $transaction->id)->where('transaction_category', 'PARENT_MANAGED_AIRTIME_DEBIT')->count())->toBe(1);
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

it('rejects a stale or inaccessible affiliate airtime plan without touching either wallet', function () {
    $f = airtimeControllerFixture();
    config()->set('parent_businesses.features.parent_managed_purchases', true);
    config()->set('parent_businesses.features.provider_routing', true);
    $before = $f['customer']->main_wallet;

    $this->mock(ParentManagedPurchaseOrchestrator::class, fn (MockInterface $mock) => $mock->shouldNotReceive('purchase'));
    $this->mock(ParentPurchaseExecutor::class, fn (MockInterface $mock) => $mock->shouldNotReceive('execute'));

    $this->withoutMiddleware()->actingAs($f['customer'])->withSession(['affiliate' => $f['affiliate']])->postJson('/user/airtime/store2', [
        'network_id' => $f['network']->id,
        'phone_number' => '08030000000',
        'product_plan_id' => 999999,
        'pin' => '1234',
        'amount' => 1000,
        'validatephonenetwork' => 0,
        'wallet_category' => 'main_wallet',
    ])->assertOk()
        ->assertJsonPath('status', -1)
        ->assertJsonPath('message', 'The selected airtime plan is unavailable.');

    expect($f['customer']->fresh()->main_wallet)->toBe($before)
        ->and(Transaction::withoutGlobalScope('affiliate')->doesntExist())->toBeTrue();
});

it('does not fall back to legacy airtime when a multi-parent feature flag is disabled', function () {
    $f = airtimeControllerFixture();
    config()->set('parent_businesses.features.parent_managed_purchases', false);
    config()->set('parent_businesses.features.provider_routing', true);

    $this->mock(ParentManagedPurchaseOrchestrator::class, fn (MockInterface $mock) => $mock->shouldNotReceive('purchase'));
    $this->mock(ParentPurchaseExecutor::class, fn (MockInterface $mock) => $mock->shouldNotReceive('execute'));

    $response = $this->withoutMiddleware()->actingAs($f['customer'])->withSession(['affiliate' => $f['affiliate']])->postJson('/user/airtime/store2', [
        'network_id' => $f['network']->id, 'phone_number' => '08030000000', 'product_plan_id' => $f['affiliatePlan']->id,
        'pin' => '1234', 'amount' => 1000, 'validatephonenetwork' => 0, 'wallet_category' => 'main_wallet',
    ]);

    $response->assertOk()
        ->assertJsonPath('status', -1)
        ->assertJsonPath('message', 'Parent-managed Airtime purchasing is not enabled.');
    expect($f['customer']->fresh()->main_wallet)->toBe('1500.00')
        ->and(Transaction::withoutGlobalScope('affiliate')->doesntExist())->toBeTrue();
});

it('does not fall back to legacy airtime when rollout is disabled for a multi-parent affiliate', function () {
    $f = airtimeControllerFixture();
    config()->set('parent_businesses.features.parent_managed_purchases', true);
    config()->set('parent_businesses.features.provider_routing', true);
    ProviderRoutingRollout::query()->delete();

    $this->mock(ParentManagedPurchaseOrchestrator::class, fn (MockInterface $mock) => $mock->shouldNotReceive('purchase'));
    $this->mock(ParentPurchaseExecutor::class, fn (MockInterface $mock) => $mock->shouldNotReceive('execute'));

    $this->withoutMiddleware()->actingAs($f['customer'])->withSession(['affiliate' => $f['affiliate']])->postJson('/user/airtime/store2', [
        'network_id' => $f['network']->id, 'phone_number' => '08030000000', 'product_plan_id' => $f['affiliatePlan']->id,
        'pin' => '1234', 'amount' => 1000, 'validatephonenetwork' => 0, 'wallet_category' => 'main_wallet',
    ])->assertOk()
        ->assertJsonPath('status', -1)
        ->assertJsonPath('message', 'Airtime purchasing is not enabled for this affiliate.');

    expect($f['customer']->fresh()->main_wallet)->toBe('1500.00')
        ->and(Transaction::withoutGlobalScope('affiliate')->doesntExist())->toBeTrue();
});

it('retains the legacy airtime path for an explicit legacy oresamsub profile', function () {
    $f = airtimeControllerFixture();
    config()->set('parent_businesses.features.parent_managed_purchases', false);
    config()->set('parent_businesses.features.provider_routing', false);
    $f['affiliate']->processingProfile()->update([
        'management_mode' => 'affiliate_managed',
        'processing_engine' => 'legacy_oresamsub',
    ]);
    $f['customer']->update(['main_wallet' => '0.00']);

    $this->mock(ParentManagedPurchaseOrchestrator::class, fn (MockInterface $mock) => $mock->shouldNotReceive('purchase'));
    $this->mock(ParentPurchaseExecutor::class, fn (MockInterface $mock) => $mock->shouldNotReceive('execute'));

    $this->withoutMiddleware()->actingAs($f['customer'])->withSession(['affiliate' => $f['affiliate']])->postJson('/user/airtime/store2', [
        'network_id' => $f['network']->id, 'phone_number' => '08030000000', 'product_plan_id' => $f['affiliatePlan']->id,
        'pin' => '1234', 'amount' => 1000, 'validatephonenetwork' => 0, 'wallet_category' => 'main_wallet',
    ])->assertOk()->assertJsonPath('status', -1)->assertJsonPath('message', 'Insufficient wallet balance');
});
