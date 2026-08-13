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
use App\Models\User;
use App\Services\Providers\ConfigurableProviderClient;
use App\Services\Providers\ParentManagedPurchaseOrchestrator;
use App\Services\Providers\ParentPurchaseExecutor;
use App\Services\Providers\PurchaseRouteResolver;
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
        ->and($wallet->reserved_balance)->toBe('0.00');
});

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
});
