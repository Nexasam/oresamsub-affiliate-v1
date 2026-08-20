<?php

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\AffiliateProcessingProfile;
use App\Models\AffiliateServiceProfitCap;
use App\Models\AffiliateSettlementWallet;
use App\Models\ParentBusiness;
use App\Models\ParentDefaultProfitRule;
use App\Models\ParentProviderConnection;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Models\ProviderConnection;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletLog;
use App\Services\Providers\ConfigurableProviderClient;
use App\Services\Providers\ParentManagedPurchaseOrchestrator;
use App\Services\Providers\ParentPurchaseExecutor;
use App\Services\Providers\PurchaseRouteResolver;
use App\Services\Reconciliation\TransactionFinancialReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

function executableParentPurchase(): array
{
    $parent = ParentBusiness::create(['name' => 'Executor Parent', 'slug' => 'executor-parent']);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create([
        'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id,
        'name' => 'Executor Affiliate', 'slug' => 'executor-affiliate', 'affiliate_plan_id' => 1,
        'ip_address' => '127.8.0.1', 'contact_phone' => '08050000031',
        'contact_email' => 'executor@example.test', 'parent_key' => 'executor-key',
        'parent_email' => 'executor-parent@example.test',
    ]);
    $adapter = ProviderConnection::create(['name' => 'HTTP', 'slug' => 'executor-http', 'adapter' => 'configurable_http', 'capabilities' => ['services' => ['data']], 'status' => 'active']);
    $connection = ParentProviderConnection::create(['parent_business_id' => $parent->id, 'provider_connection_id' => $adapter->id, 'name' => 'Primary', 'status' => 'active', 'approval_status' => 'approved']);
    $product = Product::create(['api_id' => 'executor-data', 'product_name' => 'Data', 'slug' => 'data']);
    $category = ProductPlanCategory::create(['api_id' => 'executor-category', 'product_id' => $product->id, 'product_plan_category_name' => 'SME']);
    $plan = ProductPlan::create(['parent_business_id' => $parent->id, 'product_plan_category_id' => $category->id, 'product_plan_name' => '1GB', 'cost_price' => '100.00', 'visibility' => 1, 'affiliate_visibility' => 1]);
    $route = $plan->providerRoutes()->create(['parent_business_id' => $parent->id, 'parent_provider_connection_id' => $connection->id, 'provider_plan_id' => 'PAUL-1GB', 'priority' => 1, 'active' => true]);
    $affiliatePlan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create(['affiliate_id' => $affiliate->id, 'product_plan_id' => $plan->id, 'product_plan_name' => '1GB', 'visibility' => 1, 'visibility_from_admin' => 1]);

    return compact('parent', 'level', 'affiliate', 'adapter', 'connection', 'product', 'plan', 'route', 'affiliatePlan');
}

function executableAirtimePurchase(string $suffix): array
{
    $f = executableParentPurchase();
    $f['product']->update(['product_name' => 'Airtime', 'slug' => 'airtime']);
    $f['plan']->update(['cost_price' => '950.00', 'profit_category' => 'percent']);
    $f['affiliatePlan']->update(['user_level_1_profit' => '1.00']);
    ParentDefaultProfitRule::create(['parent_business_id' => $f['parent']->id, 'parent_reseller_level_id' => $f['level']->id, 'product_id' => $f['product']->id, 'calculation_type' => 'percent_discount', 'value' => '3.00']);
    AffiliateServiceProfitCap::create(['parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id, 'product_id' => $f['product']->id, 'customer_level' => 1, 'calculation_type' => 'percent', 'max_value' => '1.00']);
    AffiliateProcessingProfile::create(['affiliate_id' => $f['affiliate']->id, 'parent_business_id' => $f['parent']->id, 'management_mode' => 'parent_managed', 'processing_engine' => 'multi_parent', 'status' => 'active']);
    AffiliateSettlementWallet::create(['affiliate_id' => $f['affiliate']->id, 'parent_business_id' => $f['parent']->id, 'available_balance' => '1500.00', 'reserved_balance' => '0.00', 'currency' => 'NGN', 'status' => 'active']);
    $userPlanId = DB::table('affiliate_user_plans')->insertGetId(['affiliate_id' => $f['affiliate']->id, 'user_plan_name' => 'Basic '.$suffix, 'plan_level' => '1', 'is_default' => '1', 'visibility' => '1', 'created_at' => now(), 'updated_at' => now()]);
    $f['customer'] = User::factory()->create(['affiliate_id' => $f['affiliate']->id, 'user_plan_id' => $userPlanId, 'main_wallet' => '1500.00']);

    return $f;
}

it('executes a resolved parent route with its provider plan reference and returns a transaction snapshot', function () {
    $fixture = executableParentPurchase();
    $affiliatePlan = $fixture['affiliatePlan'];

    $this->mock(ConfigurableProviderClient::class, function (MockInterface $mock) {
        $mock->shouldReceive('execute')->once()->withArgs(function ($connection, $service, $runtime) {
            return $service === 'data'
                && $runtime['plan'] === 'PAUL-1GB'
                && $runtime['provider_plan_id'] === 'PAUL-1GB'
                && $runtime['reference'] === 'ORDER-100';
        })->andReturn([
            'successful' => true,
            'ambiguous' => false,
            'message' => 'Delivered',
            'provider_reference' => 'PAUL-REF-1',
            'http_status' => 200,
            'provider_response' => ['status' => 'success'],
        ]);
    });

    $result = app(ParentPurchaseExecutor::class)->execute($affiliatePlan, [
        'phone_number' => '08030000000',
        'plan' => 'OLD-INTERNAL-ID',
        'reference' => 'ORDER-100',
    ]);

    expect($result)->toMatchArray([
        'status' => 1,
        'ambiguous' => false,
        'user_message' => 'Delivered',
        'provider_reference' => 'PAUL-REF-1',
        'parent_business_id' => $fixture['parent']->id,
        'provider_plan_id_snapshot' => 'PAUL-1GB',
        'routing_status' => 'successful',
    ]);
});

it('keeps timeouts pending for reconciliation and marks conclusive failures refundable', function (bool $ambiguous, int $status, string $routingStatus) {
    $fixture = executableParentPurchase();

    $this->mock(PurchaseRouteResolver::class, fn (MockInterface $mock) => $mock
        ->shouldReceive('resolve')->once()->andReturn([
            'affiliate' => $fixture['affiliate'], 'parent' => $fixture['parent'],
            'affiliate_product_plan' => $fixture['affiliatePlan'], 'product_plan' => $fixture['plan'],
            'route' => $fixture['route'], 'connection' => $fixture['connection'],
            'adapter' => $fixture['adapter'], 'provider_plan_id' => 'PAUL-1GB', 'product_slug' => 'data',
        ]));
    $this->mock(ConfigurableProviderClient::class, fn (MockInterface $mock) => $mock
        ->shouldReceive('execute')->once()->andReturn([
            'successful' => false, 'ambiguous' => $ambiguous, 'message' => 'Provider issue',
            'provider_reference' => null, 'http_status' => null, 'provider_response' => null,
        ]));

    $result = app(ParentPurchaseExecutor::class)->execute($fixture['affiliatePlan'], ['reference' => 'ORDER-101']);

    expect($result['status'])->toBe($status)
        ->and($result['routing_status'])->toBe($routingStatus)
        ->and($result['requires_reconciliation'])->toBe($ambiguous)
        ->and($result['refundable'])->toBe(! $ambiguous);
})->with([
    'ambiguous timeout' => [true, 0, 'reconciliation_required'],
    'conclusive failure' => [false, 2, 'failed'],
]);

it('orchestrates a parent-managed data purchase with pricing snapshots and settlement capture', function () {
    $f = executableParentPurchase();
    $f['affiliatePlan']->update(['user_level_1_profit' => '10.00']);
    ParentDefaultProfitRule::create(['parent_business_id' => $f['parent']->id, 'parent_reseller_level_id' => $f['level']->id, 'product_id' => $f['product']->id, 'calculation_type' => 'flat', 'value' => '20.00']);
    AffiliateServiceProfitCap::create(['parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id, 'product_id' => $f['product']->id, 'customer_level' => 1, 'calculation_type' => 'flat', 'max_value' => '70.00']);
    AffiliateProcessingProfile::create(['affiliate_id' => $f['affiliate']->id, 'parent_business_id' => $f['parent']->id, 'management_mode' => 'parent_managed', 'processing_engine' => 'multi_parent', 'status' => 'active']);
    AffiliateSettlementWallet::create(['affiliate_id' => $f['affiliate']->id, 'parent_business_id' => $f['parent']->id, 'available_balance' => '500.00', 'reserved_balance' => '0.00', 'currency' => 'NGN', 'status' => 'active']);
    $userPlanId = DB::table('affiliate_user_plans')->insertGetId(['affiliate_id' => $f['affiliate']->id, 'user_plan_name' => 'Basic', 'plan_level' => '1', 'is_default' => '1', 'visibility' => '1', 'created_at' => now(), 'updated_at' => now()]);
    $customer = User::factory()->create(['affiliate_id' => $f['affiliate']->id, 'user_plan_id' => $userPlanId, 'main_wallet' => '1000.00']);

    $this->mock(ParentPurchaseExecutor::class, fn (MockInterface $mock) => $mock->shouldReceive('execute')->once()->andReturn([
        'status' => 1, 'successful' => true, 'ambiguous' => false, 'routing_status' => 'successful',
        'provider_reference' => 'UPSTREAM-1', 'parent_provider_connection_id' => $f['connection']->id,
        'product_plan_provider_route_id' => $f['route']->id, 'provider_plan_id_snapshot' => 'PAUL-1GB',
        'user_message' => 'Delivered', 'admin_message' => 'Delivered',
        'provider_response' => ['success' => true, 'message' => 'Transaction processed successfully.', 'api_token' => '[REDACTED]'],
    ]));

    $result = app(ParentManagedPurchaseOrchestrator::class)->purchase($customer, $f['affiliatePlan']->fresh(), [
        'reference' => 'ORDER-PARENT-1', 'phone_number' => '08030000000',
    ], 1);
    $wallet = AffiliateSettlementWallet::where('affiliate_id', $f['affiliate']->id)->first();

    expect($result['transaction']->status)->toBe('1')
        ->and($result['transaction']->provider_cost_snapshot)->toBe('100.00')
        ->and($result['transaction']->affiliate_cost_snapshot)->toBe('120.00')
        ->and($result['transaction']->customer_price_snapshot)->toBe('130.00')
        ->and($result['transaction']->provider_response)->toBe([
            'success' => true, 'message' => 'Transaction processed successfully.', 'api_token' => '[REDACTED]',
        ])
        ->and($customer->fresh()->main_wallet)->toBe('870.00')
        ->and($wallet->available_balance)->toBe('380.00')
        ->and($wallet->reserved_balance)->toBe('0.00')
        ->and(app(TransactionFinancialReconciliationService::class)->audit($result['transaction'])['balanced'])->toBeTrue();

    $this->artisan('parent-purchases:audit-financials', ['--reference' => 'ORDER-PARENT-1'])
        ->expectsOutputToContain('1 balanced, 0 mismatch')
        ->assertSuccessful();
});

it('replaces estimated provider cost with the confirmed provider charge on success', function () {
    $f = executableParentPurchase();
    $f['affiliatePlan']->update(['user_level_1_profit' => '10.00']);
    ParentDefaultProfitRule::create(['parent_business_id' => $f['parent']->id, 'parent_reseller_level_id' => $f['level']->id, 'product_id' => $f['product']->id, 'calculation_type' => 'flat', 'value' => '20.00']);
    AffiliateServiceProfitCap::create(['parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id, 'product_id' => $f['product']->id, 'customer_level' => 1, 'calculation_type' => 'flat', 'max_value' => '70.00']);
    AffiliateProcessingProfile::create(['affiliate_id' => $f['affiliate']->id, 'parent_business_id' => $f['parent']->id, 'management_mode' => 'parent_managed', 'processing_engine' => 'multi_parent', 'status' => 'active']);
    AffiliateSettlementWallet::create(['affiliate_id' => $f['affiliate']->id, 'parent_business_id' => $f['parent']->id, 'available_balance' => '500.00', 'reserved_balance' => '0.00', 'currency' => 'NGN', 'status' => 'active']);
    $userPlanId = DB::table('affiliate_user_plans')->insertGetId(['affiliate_id' => $f['affiliate']->id, 'user_plan_name' => 'Basic', 'plan_level' => '1', 'is_default' => '1', 'visibility' => '1', 'created_at' => now(), 'updated_at' => now()]);
    $customer = User::factory()->create(['affiliate_id' => $f['affiliate']->id, 'user_plan_id' => $userPlanId, 'main_wallet' => '1000.00']);

    $this->mock(ParentPurchaseExecutor::class, fn (MockInterface $mock) => $mock->shouldReceive('execute')->once()->andReturn([
        'status' => 1, 'successful' => true, 'ambiguous' => false, 'routing_status' => 'successful',
        'actual_provider_charge' => '99.00', 'provider_reference' => 'UPSTREAM-ACTUAL-COST',
        'parent_provider_connection_id' => $f['connection']->id, 'product_plan_provider_route_id' => $f['route']->id,
        'provider_plan_id_snapshot' => 'PAUL-1GB', 'user_message' => 'Delivered', 'admin_message' => 'Delivered',
        'provider_response' => ['success' => true, 'data' => ['discounted_amount' => '99.00']],
    ]));

    $transaction = app(ParentManagedPurchaseOrchestrator::class)->purchase($customer, $f['affiliatePlan']->fresh(), [
        'reference' => 'ORDER-ACTUAL-PROVIDER-COST', 'phone_number' => '08030000000',
    ], 1)['transaction'];

    expect($transaction->provider_cost_snapshot)->toBe('99.00')
        ->and($transaction->parent_cost_snapshot)->toBe('99.00')
        ->and($transaction->affiliate_cost_snapshot)->toBe('120.00')
        ->and($transaction->parent_profit_snapshot)->toBe('21.00')
        ->and($transaction->affiliate_profit_snapshot)->toBe('10.00')
        ->and(app(TransactionFinancialReconciliationService::class)->audit($transaction)['balanced'])->toBeTrue();
});

it('orchestrates parent-managed airtime with percentage pricing and service-aware financial records', function () {
    $f = executableParentPurchase();
    $f['product']->update(['product_name' => 'Airtime', 'slug' => 'airtime']);
    $f['plan']->update(['cost_price' => '950.00', 'profit_category' => 'percent']);
    $f['affiliatePlan']->update(['user_level_1_profit' => '1.00']);
    ParentDefaultProfitRule::create(['parent_business_id' => $f['parent']->id, 'parent_reseller_level_id' => $f['level']->id, 'product_id' => $f['product']->id, 'calculation_type' => 'percent_discount', 'value' => '3.00']);
    AffiliateServiceProfitCap::create(['parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id, 'product_id' => $f['product']->id, 'customer_level' => 1, 'calculation_type' => 'percent', 'max_value' => '1.00']);
    AffiliateProcessingProfile::create(['affiliate_id' => $f['affiliate']->id, 'parent_business_id' => $f['parent']->id, 'management_mode' => 'parent_managed', 'processing_engine' => 'multi_parent', 'status' => 'active']);
    AffiliateSettlementWallet::create(['affiliate_id' => $f['affiliate']->id, 'parent_business_id' => $f['parent']->id, 'available_balance' => '1500.00', 'reserved_balance' => '0.00', 'currency' => 'NGN', 'status' => 'active']);
    $userPlanId = DB::table('affiliate_user_plans')->insertGetId(['affiliate_id' => $f['affiliate']->id, 'user_plan_name' => 'Basic', 'plan_level' => '1', 'is_default' => '1', 'visibility' => '1', 'created_at' => now(), 'updated_at' => now()]);
    $customer = User::factory()->create(['affiliate_id' => $f['affiliate']->id, 'user_plan_id' => $userPlanId, 'main_wallet' => '1500.00']);

    $this->mock(ParentPurchaseExecutor::class, fn (MockInterface $mock) => $mock->shouldReceive('execute')->once()->withArgs(fn ($plan, $runtime) => $runtime['amount'] === '1000.00' && $runtime['service'] === 'airtime')->andReturn([
        'status' => 1, 'successful' => true, 'ambiguous' => false, 'routing_status' => 'successful',
        'provider_reference' => 'AIRTIME-UPSTREAM-1', 'parent_provider_connection_id' => $f['connection']->id,
        'product_plan_provider_route_id' => $f['route']->id, 'provider_plan_id_snapshot' => 'PAUL-1GB',
        'user_message' => 'Airtime delivered', 'admin_message' => 'Airtime delivered',
        'provider_response' => ['success' => true, 'message' => 'Airtime delivered'],
    ]));

    $result = app(ParentManagedPurchaseOrchestrator::class)->purchase($customer, $f['affiliatePlan']->fresh(), [
        'reference' => 'AIRTIME-PARENT-1', 'service' => 'airtime', 'amount' => '1000.00',
        'phone_number' => '08030000000', 'network' => 'MTN',
    ], 1, '1000.00');
    $transaction = $result['transaction'];

    expect($transaction->transaction_category)->toBe('airtime')
        ->and($transaction->description)->toBe('Parent-managed airtime purchase')
        ->and($transaction->face_value_snapshot)->toBe('1000.00')
        ->and($transaction->amount)->toBe('990.00')
        ->and($transaction->discounted_amount)->toBe('990.00')
        ->and($customer->fresh()->main_wallet)->toBe('510.00')
        ->and(WalletLog::withoutGlobalScope('affiliate')->where('transaction_id', $transaction->id)->value('transaction_category'))->toBe('PARENT_MANAGED_AIRTIME_DEBIT')
        ->and(app(TransactionFinancialReconciliationService::class)->audit($transaction)['balanced'])->toBeTrue();
});

it('applies the correct airtime financial outcome for provider failures and ambiguous responses', function (bool $ambiguous, string $routingStatus, string $expectedCustomerBalance, string $expectedAvailable, string $expectedReserved, int $refunds) {
    $f = executableAirtimePurchase($routingStatus);
    $providerResult = [
        'status' => $ambiguous ? 0 : 2, 'successful' => false, 'ambiguous' => $ambiguous,
        'routing_status' => $routingStatus, 'provider_reference' => null,
        'parent_provider_connection_id' => $f['connection']->id, 'product_plan_provider_route_id' => $f['route']->id,
        'provider_plan_id_snapshot' => 'PAUL-AIRTIME', 'user_message' => 'Provider issue',
        'admin_message' => 'Provider issue', 'provider_response' => ['success' => false],
    ];
    $this->mock(ParentPurchaseExecutor::class, fn (MockInterface $mock) => $mock->shouldReceive('execute')->once()->andReturn($providerResult));

    $result = app(ParentManagedPurchaseOrchestrator::class)->purchase($f['customer'], $f['affiliatePlan']->fresh(), [
        'reference' => 'AIRTIME-'.strtoupper($routingStatus), 'service' => 'airtime', 'amount' => '1000.00',
        'phone_number' => '08030000000', 'network' => 'MTN',
    ], 1, '1000.00');
    $wallet = AffiliateSettlementWallet::where('affiliate_id', $f['affiliate']->id)->first();

    expect($result['transaction']->routing_status)->toBe($routingStatus)
        ->and($f['customer']->fresh()->main_wallet)->toBe($expectedCustomerBalance)
        ->and($wallet->available_balance)->toBe($expectedAvailable)
        ->and($wallet->reserved_balance)->toBe($expectedReserved)
        ->and(WalletLog::withoutGlobalScope('affiliate')->where('transaction_id', $result['transaction']->id)->where('transaction_category', 'PARENT_MANAGED_AIRTIME_REFUND')->count())->toBe($refunds)
        ->and(app(TransactionFinancialReconciliationService::class)->audit($result['transaction'])['balanced'])->toBeTrue();
})->with([
    'conclusive failure refunds and releases' => [false, 'failed', '1500.00', '1500.00', '0.00', 1],
    'ambiguous response stays reserved without refund' => [true, 'reconciliation_required', '510.00', '530.00', '970.00', 0],
]);

it('does not call the airtime provider when customer or settlement funds are insufficient', function (string $shortfall) {
    $f = executableAirtimePurchase('insufficient-'.$shortfall);
    if ($shortfall === 'customer') {
        $f['customer']->update(['main_wallet' => '500.00']);
    } else {
        AffiliateSettlementWallet::where('affiliate_id', $f['affiliate']->id)->update(['available_balance' => '500.00']);
    }

    $this->mock(ParentPurchaseExecutor::class, fn (MockInterface $mock) => $mock->shouldNotReceive('execute'));

    expect(fn () => app(ParentManagedPurchaseOrchestrator::class)->purchase($f['customer']->fresh(), $f['affiliatePlan']->fresh(), [
        'reference' => 'AIRTIME-INSUFFICIENT-'.strtoupper($shortfall), 'service' => 'airtime', 'amount' => '1000.00',
        'phone_number' => '08030000000', 'network' => 'MTN',
    ], 1, '1000.00'))->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(Transaction::withoutGlobalScope('affiliate')->where('txn_reference', 'AIRTIME-INSUFFICIENT-'.strtoupper($shortfall))->doesntExist())->toBeTrue()
        ->and(WalletLog::withoutGlobalScope('affiliate')->where('transaction_category', 'PARENT_MANAGED_AIRTIME_DEBIT')->doesntExist())->toBeTrue();
})->with(['customer', 'settlement']);

it('refunds a conclusive provider failure and releases settlement exactly once', function () {
    $f = executableParentPurchase();
    $f['affiliatePlan']->update(['user_level_1_profit' => '10.00']);
    ParentDefaultProfitRule::create(['parent_business_id' => $f['parent']->id, 'parent_reseller_level_id' => $f['level']->id, 'product_id' => $f['product']->id, 'calculation_type' => 'flat', 'value' => '20.00']);
    AffiliateServiceProfitCap::create(['parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id, 'product_id' => $f['product']->id, 'customer_level' => 1, 'calculation_type' => 'flat', 'max_value' => '70.00']);
    AffiliateProcessingProfile::create(['affiliate_id' => $f['affiliate']->id, 'parent_business_id' => $f['parent']->id, 'management_mode' => 'parent_managed', 'processing_engine' => 'multi_parent', 'status' => 'active']);
    AffiliateSettlementWallet::create(['affiliate_id' => $f['affiliate']->id, 'parent_business_id' => $f['parent']->id, 'available_balance' => '500.00', 'reserved_balance' => '0.00', 'currency' => 'NGN', 'status' => 'active']);
    $userPlanId = DB::table('affiliate_user_plans')->insertGetId(['affiliate_id' => $f['affiliate']->id, 'user_plan_name' => 'Basic', 'plan_level' => '1', 'is_default' => '1', 'visibility' => '1', 'created_at' => now(), 'updated_at' => now()]);
    $customer = User::factory()->create(['affiliate_id' => $f['affiliate']->id, 'user_plan_id' => $userPlanId, 'main_wallet' => '1000.00']);

    $failure = [
        'status' => 2, 'successful' => false, 'ambiguous' => false, 'routing_status' => 'failed',
        'provider_reference' => 'REJECTED-1', 'parent_provider_connection_id' => $f['connection']->id,
        'product_plan_provider_route_id' => $f['route']->id, 'provider_plan_id_snapshot' => 'PAUL-1GB',
        'user_message' => 'Transaction failed.', 'admin_message' => 'Provider rejected the request.',
        'provider_response' => ['success' => false, 'message' => 'Insufficient provider balance.'],
    ];
    $this->mock(ParentPurchaseExecutor::class, fn (MockInterface $mock) => $mock->shouldReceive('execute')->once()->andReturn($failure));

    $service = app(ParentManagedPurchaseOrchestrator::class);
    $result = $service->purchase($customer, $f['affiliatePlan']->fresh(), [
        'reference' => 'ORDER-FAILED-ONCE', 'phone_number' => '08030000000',
    ], 1);
    $service->finalize($result['transaction']->fresh(), $f['affiliate'], $customer, $failure);

    $wallet = AffiliateSettlementWallet::where('affiliate_id', $f['affiliate']->id)->first();
    expect($result['transaction']->routing_status)->toBe('failed')
        ->and($result['transaction']->status)->toBe('2')
        ->and($customer->fresh()->main_wallet)->toBe('1000.00')
        ->and($wallet->available_balance)->toBe('500.00')
        ->and($wallet->reserved_balance)->toBe('0.00')
        ->and($wallet->ledgerEntries()->where('entry_type', 'purchase_reservation')->count())->toBe(1)
        ->and($wallet->ledgerEntries()->where('entry_type', 'reservation_release')->count())->toBe(1)
        ->and(\App\Models\WalletLog::withoutGlobalScope('affiliate')->where('user_id', $customer->id)->where('transaction_category', 'PARENT_MANAGED_DATA_REFUND')->count())->toBe(1);

    expect(app(TransactionFinancialReconciliationService::class)->audit($result['transaction'])['balanced'])->toBeTrue();
});

it('flags a parent-managed purchase whose financial snapshots no longer reconcile', function () {
    $f = executableParentPurchase();
    $userPlanId = DB::table('affiliate_user_plans')->insertGetId(['affiliate_id' => $f['affiliate']->id, 'user_plan_name' => 'Basic', 'plan_level' => '1', 'is_default' => '1', 'visibility' => '1', 'created_at' => now(), 'updated_at' => now()]);
    $customer = User::factory()->create(['affiliate_id' => $f['affiliate']->id, 'user_plan_id' => $userPlanId]);
    $transaction = \App\Models\Transaction::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $f['affiliate']->id,
        'parent_business_id' => $f['parent']->id,
        'api_id' => 'TAMPERED-PLAN',
        'affiliate_product_plan_id' => $f['affiliatePlan']->id,
        'user_id' => $customer->id,
        'txn_reference' => 'ORDER-TAMPERED',
        'wallet_category' => 'main_wallet',
        'amount' => '130.00',
        'balance_before' => '1000.00',
        'balance_after' => '870.00',
        'description' => 'Parent-managed data purchase',
        'status' => 1,
        'routing_status' => 'successful',
        'provider_cost_snapshot' => '100.00',
        'parent_cost_snapshot' => '100.00',
        'affiliate_cost_snapshot' => '120.00',
        'customer_price_snapshot' => '130.00',
        'parent_profit_snapshot' => '20.00',
        'affiliate_profit_snapshot' => '11.00',
    ]);

    $audit = app(TransactionFinancialReconciliationService::class)->audit($transaction);

    expect($audit['balanced'])->toBeFalse()
        ->and($audit['issues'])->toContain('Affiliate profit snapshot does not bridge affiliate cost to customer price.');
});
