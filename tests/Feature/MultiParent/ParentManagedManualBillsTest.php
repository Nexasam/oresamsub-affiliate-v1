<?php

use App\Models\Affiliate;
use App\Models\AffiliateProcessingProfile;
use App\Models\AffiliateProductPlan;
use App\Models\AffiliateProductPlanCategory;
use App\Models\AffiliateServiceProfitCap;
use App\Models\AffiliateSettlementWallet;
use App\Models\ParentBusiness;
use App\Models\ParentAdmin;
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
use App\Services\Providers\ConfigurableProviderClient;
use App\Services\Providers\ParentManagedManualPurchaseService;
use App\Services\Providers\ParentPurchaseExecutor;
use App\Services\Reconciliation\TransactionFinancialReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function manualBillFixture(string $service): array
{
    $suffix = str_replace('_', '-', $service);
    $parent = ParentBusiness::create(['name' => "Manual {$suffix}", 'slug' => "manual-{$suffix}"]);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create([
        'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id,
        'name' => "Affiliate {$suffix}", 'slug' => "affiliate-{$suffix}", 'affiliate_plan_id' => 1,
        'ip_address' => '127.7.0.1', 'contact_phone' => '08050000222',
        'contact_email' => "{$suffix}@example.test", 'parent_key' => "{$suffix}-key",
        'parent_email' => "parent-{$suffix}@example.test",
    ]);
    $adapter = ProviderConnection::create([
        'name' => 'Manual HTTP', 'slug' => "manual-http-{$suffix}", 'adapter' => "manual_http_{$suffix}",
        'capabilities' => ['services' => [$service], 'methods' => ['GET', 'POST'], 'credential_fields' => ['api_public_key']],
        'status' => 'active',
    ]);
    $connection = ParentProviderConnection::create([
        'parent_business_id' => $parent->id, 'provider_connection_id' => $adapter->id,
        'name' => 'Primary', 'status' => 'active', 'approval_status' => 'approved',
    ]);
    $product = Product::create(['api_id' => "manual-{$suffix}", 'product_name' => $service === 'utility_bills' ? 'Electricity' : 'Cable', 'slug' => $service]);
    $category = ProductPlanCategory::create(['api_id' => "category-{$suffix}", 'product_id' => $product->id, 'product_plan_category_name' => strtoupper($suffix)]);
    $plan = ProductPlan::create([
        'parent_business_id' => $parent->id, 'product_plan_category_id' => $category->id,
        'product_plan_name' => "Plan {$suffix}", 'api_id' => "PLAN-{$suffix}",
        'cost_price' => $service === 'utility_bills' ? '950.00' : '1000.00',
        'visibility' => 1, 'affiliate_visibility' => 1,
    ]);
    $route = $plan->providerRoutes()->create([
        'parent_business_id' => $parent->id, 'parent_provider_connection_id' => $connection->id,
        'provider_plan_id' => "PROVIDER-{$suffix}", 'priority' => 1, 'active' => true,
    ]);
    $affiliatePlan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'product_plan_id' => $plan->id,
        'product_plan_name' => "Plan {$suffix}", 'visibility' => 1, 'visibility_from_admin' => 1,
        'user_level_1_profit' => $service === 'utility_bills' ? '1.00' : '50.00',
    ]);
    $affiliateCategory = AffiliateProductPlanCategory::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'plan_category_id' => $category->id, 'product_id' => $product->id,
        'product_plan_category_name' => strtoupper($suffix), 'visibility' => 1,
    ]);
    ParentDefaultProfitRule::create([
        'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id,
        'product_id' => $product->id, 'calculation_type' => $service === 'utility_bills' ? 'percent_discount' : 'flat',
        'value' => $service === 'utility_bills' ? '3.00' : '50.00',
    ]);
    AffiliateServiceProfitCap::create([
        'parent_business_id' => $parent->id, 'affiliate_id' => $affiliate->id,
        'product_id' => $product->id, 'customer_level' => 1,
        'calculation_type' => $service === 'utility_bills' ? 'percent' : 'flat',
        'max_value' => $service === 'utility_bills' ? '1.00' : '50.00',
    ]);
    AffiliateProcessingProfile::create([
        'affiliate_id' => $affiliate->id, 'parent_business_id' => $parent->id,
        'management_mode' => 'parent_managed', 'processing_engine' => 'multi_parent', 'status' => 'active',
    ]);
    ProviderRoutingRollout::create([
        'parent_business_id' => $parent->id, 'scope_type' => 'affiliate', 'scope_id' => $affiliate->id,
        'service' => $service, 'enabled' => true,
    ]);
    AffiliateSettlementWallet::create([
        'affiliate_id' => $affiliate->id, 'parent_business_id' => $parent->id,
        'available_balance' => '5000.00', 'reserved_balance' => '0.00', 'currency' => 'NGN', 'status' => 'active',
    ]);
    $userPlanId = DB::table('affiliate_user_plans')->insertGetId([
        'affiliate_id' => $affiliate->id, 'user_plan_name' => 'Basic', 'plan_level' => 1,
        'is_default' => 1, 'visibility' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $customer = User::factory()->create(['affiliate_id' => $affiliate->id, 'user_plan_id' => $userPlanId, 'main_wallet' => '5000.00', 'pin' => '1234', 'email_verified_at' => now()]);

    return compact('parent', 'level', 'affiliate', 'connection', 'product', 'plan', 'route', 'affiliatePlan', 'affiliateCategory', 'customer');
}

it('validates and records cable and electricity as manual pending without vending', function (string $service, string $identifierKey, string $identifierValue, ?string $faceAmount, string $customerPrice, string $affiliateCost) {
    $f = manualBillFixture($service);
    $this->mock(ConfigurableProviderClient::class, fn (MockInterface $mock) => $mock->shouldReceive('validateCustomer')->once()->andReturn([
        'successful' => true, 'ambiguous' => false, 'message' => 'Customer verified',
        'customer_name' => 'Verified Customer', 'customer_address' => 'Lagos',
        'provider_response' => ['success' => true],
    ]));
    $this->mock(ParentPurchaseExecutor::class, fn (MockInterface $mock) => $mock->shouldNotReceive('execute'));

    $transaction = app(ParentManagedManualPurchaseService::class)->submit($f['customer'], $f['affiliatePlan'], [
        'reference' => strtoupper($service).'-MANUAL-1', 'service' => $service,
        $identifierKey => $identifierValue, 'plan' => $f['route']->provider_plan_id,
    ], 1, $faceAmount);

    $wallet = AffiliateSettlementWallet::where('affiliate_id', $f['affiliate']->id)->first();
    expect($transaction->routing_status)->toBe('manual_pending')
        ->and((int) $transaction->status)->toBe(0)
        ->and($transaction->user_screen_message)->toBe('Your transaction is being processed.')
        ->and($transaction->admin_screen_message)->toBe('Validated and awaiting parent manual processing.')
        ->and($transaction->customer_price_snapshot)->toBe($customerPrice)
        ->and($transaction->affiliate_cost_snapshot)->toBe($affiliateCost)
        ->and($transaction->face_value_snapshot)->toBe($faceAmount === null ? null : number_format((float) $faceAmount, 2, '.', ''))
        ->and($transaction->{$service === 'utility_bills' ? 'metre_number' : 'smart_card_number'})->toBe($identifierValue)
        ->and(data_get($transaction->provider_response, 'validation.customer_name'))->toBe('Verified Customer')
        ->and($f['customer']->fresh()->main_wallet)->toBe(number_format(5000 - (float) $customerPrice, 2, '.', ''))
        ->and($wallet->reserved_balance)->toBe($affiliateCost)
        ->and(WalletLog::withoutGlobalScope('affiliate')->where('transaction_id', $transaction->id)->count())->toBe(1);
})->with([
    'cable' => ['cable_subscription', 'smartcard_number', '1234567890', null, '1100.00', '1050.00'],
    'electricity' => ['utility_bills', 'meter_number', '01234567890', '1000.00', '990.00', '970.00'],
]);

it('completes a manual pending bill exactly once with the correct financial outcome', function (string $outcome, string $customerBalance, string $available, int $refunds) {
    $f = manualBillFixture('cable_subscription');
    $admin = ParentAdmin::create([
        'parent_business_id' => $f['parent']->id, 'name' => 'Parent Operator',
        'email' => "operator-{$outcome}@example.test", 'password' => 'password', 'active' => true,
    ]);
    $this->mock(ConfigurableProviderClient::class, fn (MockInterface $mock) => $mock->shouldReceive('validateCustomer')->once()->andReturn([
        'successful' => true, 'message' => 'Verified', 'customer_name' => 'Cable Customer', 'provider_response' => [],
    ]));
    $service = app(ParentManagedManualPurchaseService::class);
    $transaction = $service->submit($f['customer'], $f['affiliatePlan'], [
        'reference' => 'CABLE-MANUAL-'.strtoupper($outcome), 'service' => 'cable_subscription',
        'smartcard_number' => '1234567890', 'plan' => $f['route']->provider_plan_id,
    ], 1);

    $completed = $service->complete($transaction, $admin, $outcome, 'Manually checked');
    $again = $service->complete($completed, $admin, $outcome, 'Repeated click');
    $wallet = AffiliateSettlementWallet::where('affiliate_id', $f['affiliate']->id)->first();

    expect($completed->routing_status)->toBe($outcome === 'successful' ? 'manual_successful' : 'manual_failed')
        ->and((int) $completed->status)->toBe($outcome === 'successful' ? 1 : 2)
        ->and($again->routing_status)->toBe($completed->routing_status)
        ->and($f['customer']->fresh()->main_wallet)->toBe($customerBalance)
        ->and($wallet->fresh()->available_balance)->toBe($available)
        ->and($wallet->fresh()->reserved_balance)->toBe('0.00')
        ->and(WalletLog::withoutGlobalScope('affiliate')->where('transaction_id', $transaction->id)->where('transaction_category', 'PARENT_MANAGED_CABLE_SUBSCRIPTION_REFUND')->count())->toBe($refunds)
        ->and(app(TransactionFinancialReconciliationService::class)->audit($completed)['balanced'])->toBeTrue();
})->with([
    'success captures reservation' => ['successful', '3900.00', '3950.00', 0],
    'failure releases and refunds' => ['failed', '5000.00', '5000.00', 1],
]);

it('prevents a parent admin from completing another parents manual transaction', function () {
    $f = manualBillFixture('cable_subscription');
    $foreignParent = ParentBusiness::create(['name' => 'Foreign Parent', 'slug' => 'foreign-parent']);
    $foreignAdmin = ParentAdmin::create([
        'parent_business_id' => $foreignParent->id, 'name' => 'Foreign Operator',
        'email' => 'foreign-operator@example.test', 'password' => 'password', 'active' => true,
    ]);
    $this->mock(ConfigurableProviderClient::class, fn (MockInterface $mock) => $mock->shouldReceive('validateCustomer')->once()->andReturn([
        'successful' => true, 'message' => 'Verified', 'customer_name' => 'Cable Customer', 'provider_response' => [],
    ]));
    $service = app(ParentManagedManualPurchaseService::class);
    $transaction = $service->submit($f['customer'], $f['affiliatePlan'], [
        'reference' => 'CABLE-MANUAL-FOREIGN', 'service' => 'cable_subscription',
        'smartcard_number' => '1234567890', 'plan' => $f['route']->provider_plan_id,
    ], 1);

    expect(fn () => $service->complete($transaction, $foreignAdmin, 'successful', null))
        ->toThrow(ValidationException::class);
    expect($transaction->fresh()->routing_status)->toBe('manual_pending');
});

it('returns the configured multi-parent electricity plan for a selected provider and amount', function () {
    $f = manualBillFixture('utility_bills');

    $this->withoutMiddleware()->actingAs($f['customer'])
        ->withSession(['affiliate' => $f['affiliate']])
        ->getJson(route('user.fetch_product_plans', [
            'plan_category_id' => $f['affiliateCategory']->id,
            'product_slug' => 'utility_bills',
            'amount' => 1000,
        ]))
        ->assertOk()
        ->assertJsonPath('status', '1')
        ->assertJsonPath('counter', 1)
        ->assertJsonPath('data.0.product_plan_id', $f['affiliatePlan']->id)
        ->assertJsonPath('data.0.product_plan_name', $f['plan']->product_plan_name)
        ->assertJsonPath('data.0.selling_price', '990.00');
});

it('routes controlled cable and electricity requests into manual pending processing', function (string $service, string $uri, array $payload) {
    $f = manualBillFixture($service);
    config()->set('parent_businesses.features.parent_managed_purchases', true);
    config()->set('parent_businesses.features.provider_routing', true);
    $payload = array_merge($payload, [
        $service === 'cable_subscription' ? 'cable_product_plan_id' : 'electricity_product_plan_id' => $f['affiliatePlan']->id,
        $service === 'cable_subscription' ? 'cable_product_plan_category_id' : 'electricity_product_plan_category_id' => $f['affiliateCategory']->id,
        'wallet_category' => 'main_wallet', 'no_of_slots' => 1, 'pin' => '1234',
    ]);
    $transaction = new Transaction([
        'status' => 0, 'routing_status' => 'manual_pending',
        'txn_reference' => strtoupper($service).'-HTTP-1',
        'user_screen_message' => 'Your transaction is being processed.',
    ]);
    $this->mock(ParentManagedManualPurchaseService::class, fn (MockInterface $mock) => $mock->shouldReceive('submit')->once()->andReturn($transaction));
    $this->mock(ParentPurchaseExecutor::class, fn (MockInterface $mock) => $mock->shouldNotReceive('execute'));

    $this->withoutMiddleware()->actingAs($f['customer'])->withSession(['affiliate' => $f['affiliate']])
        ->postJson($uri, $payload)
        ->assertOk()->assertJsonPath('status', 0)
        ->assertJsonPath('message', 'Your transaction is being processed.');
})->with([
    'cable' => ['cable_subscription', '/user/cable_subscription/store', [
        'smart_card_number' => '1234567890', 'validation_customer_name' => 'Verified Customer',
    ]],
    'electricity' => ['utility_bills', '/user/electricity/store', [
        'metre_number' => '01234567890', 'validation_extra_info' => 'Verified Customer',
        'validation_address' => 'Lagos', 'amount' => 1000,
    ]],
]);

it('uses the parent connection to confirm controlled smartcards and meters', function (string $service, string $uri, string $numberKey) {
    $f = manualBillFixture($service);
    config()->set('parent_businesses.features.parent_managed_purchases', true);
    config()->set('parent_businesses.features.provider_routing', true);
    $this->mock(ConfigurableProviderClient::class, fn (MockInterface $mock) => $mock->shouldReceive('validateCustomer')->once()->andReturn([
        'successful' => true, 'ambiguous' => false, 'message' => 'Customer verified',
        'customer_name' => 'Verified Customer', 'customer_address' => 'Lagos', 'provider_response' => [],
    ]));

    $this->withoutMiddleware()->actingAs($f['customer'])->withSession(['affiliate' => $f['affiliate']])
        ->getJson($uri.'?plan_id='.$f['affiliatePlan']->id.'&'.$numberKey.'=1234567890')
        ->assertOk()->assertJsonPath('status', 1)
        ->assertJsonPath('name', 'Verified Customer')
        ->assertJsonPath('address', 'Lagos');
})->with([
    'cable' => ['cable_subscription', '/user/cable_subscription/validate_smart_card_number', 'smart_card_number'],
    'electricity' => ['utility_bills', '/user/electricity/validate_metre_number', 'smart_card_number'],
]);

it('lets the owning parent admin complete a pending manual bill from transactions', function () {
    $f = manualBillFixture('cable_subscription');
    $admin = ParentAdmin::create([
        'parent_business_id' => $f['parent']->id, 'name' => 'Parent Operator',
        'email' => 'parent-completion@example.test', 'password' => 'password', 'active' => true,
    ]);
    $this->mock(ConfigurableProviderClient::class, fn (MockInterface $mock) => $mock->shouldReceive('validateCustomer')->once()->andReturn([
        'successful' => true, 'message' => 'Verified', 'customer_name' => 'Cable Customer', 'provider_response' => [],
    ]));
    $transaction = app(ParentManagedManualPurchaseService::class)->submit($f['customer'], $f['affiliatePlan'], [
        'reference' => 'CABLE-PARENT-HTTP', 'service' => 'cable_subscription',
        'smartcard_number' => '1234567890', 'plan' => $f['route']->provider_plan_id,
    ], 1);

    $this->actingAs($admin, 'parent_admin')->patch('/parent-admin/transactions/'.$transaction->id.'/manual-completion', [
        'outcome' => 'successful', 'message' => 'Decoder activated',
    ])->assertRedirect('/parent-admin/transactions');

    expect($transaction->fresh()->routing_status)->toBe('manual_successful')
        ->and((int) $transaction->fresh()->status)->toBe(1);
});

it('does not reserve or debit when manual customer validation fails', function () {
    $f = manualBillFixture('cable_subscription');
    $this->mock(ConfigurableProviderClient::class, fn (MockInterface $mock) => $mock->shouldReceive('validateCustomer')->once()->andReturn([
        'successful' => false, 'ambiguous' => false, 'message' => 'Invalid smartcard', 'provider_response' => [],
    ]));

    expect(fn () => app(ParentManagedManualPurchaseService::class)->submit($f['customer'], $f['affiliatePlan'], [
        'reference' => 'CABLE-INVALID-CUSTOMER', 'service' => 'cable_subscription',
        'smartcard_number' => '0000000000', 'plan' => $f['route']->provider_plan_id,
    ], 1))->toThrow(ValidationException::class);

    $wallet = AffiliateSettlementWallet::where('affiliate_id', $f['affiliate']->id)->first();
    expect($f['customer']->fresh()->main_wallet)->toBe('5000.00')
        ->and($wallet->available_balance)->toBe('5000.00')
        ->and($wallet->reserved_balance)->toBe('0.00')
        ->and(Transaction::withoutGlobalScope('affiliate')->where('txn_reference', 'CABLE-INVALID-CUSTOMER')->doesntExist())->toBeTrue();
});

it('does not create a manual bill when customer or settlement funds are insufficient', function (string $shortfall) {
    $f = manualBillFixture('cable_subscription');
    if ($shortfall === 'customer') {
        $f['customer']->update(['main_wallet' => '500.00']);
    } else {
        AffiliateSettlementWallet::where('affiliate_id', $f['affiliate']->id)->update(['available_balance' => '500.00']);
    }
    $this->mock(ConfigurableProviderClient::class, fn (MockInterface $mock) => $mock->shouldReceive('validateCustomer')->once()->andReturn([
        'successful' => true, 'message' => 'Verified', 'customer_name' => 'Cable Customer', 'provider_response' => [],
    ]));

    expect(fn () => app(ParentManagedManualPurchaseService::class)->submit($f['customer']->fresh(), $f['affiliatePlan'], [
        'reference' => 'CABLE-INSUFFICIENT-'.strtoupper($shortfall), 'service' => 'cable_subscription',
        'smartcard_number' => '1234567890', 'plan' => $f['route']->provider_plan_id,
    ], 1))->toThrow(ValidationException::class);

    expect(Transaction::withoutGlobalScope('affiliate')->where('txn_reference', 'CABLE-INSUFFICIENT-'.strtoupper($shortfall))->doesntExist())->toBeTrue()
        ->and(WalletLog::withoutGlobalScope('affiliate')->where('transaction_category', 'PARENT_MANAGED_CABLE_SUBSCRIPTION_DEBIT')->doesntExist())->toBeTrue();
})->with(['customer', 'settlement']);

it('keeps cable and electricity on the legacy path when parent-managed purchases are disabled', function (string $service, string $uri, array $payload) {
    $f = manualBillFixture($service);
    config()->set('parent_businesses.features.parent_managed_purchases', false);
    config()->set('parent_businesses.features.provider_routing', true);
    $f['customer']->update(['main_wallet' => '0.00']);
    $payload = array_merge($payload, [
        $service === 'cable_subscription' ? 'cable_product_plan_id' : 'electricity_product_plan_id' => $f['affiliatePlan']->id,
        $service === 'cable_subscription' ? 'cable_product_plan_category_id' : 'electricity_product_plan_category_id' => $f['affiliateCategory']->id,
        'wallet_category' => 'main_wallet', 'no_of_slots' => 1, 'pin' => '1234',
    ]);
    $this->mock(ParentManagedManualPurchaseService::class, fn (MockInterface $mock) => $mock->shouldNotReceive('submit'));
    $this->mock(ParentPurchaseExecutor::class, fn (MockInterface $mock) => $mock->shouldNotReceive('execute'));

    $this->withoutMiddleware()->actingAs($f['customer'])->withSession(['affiliate' => $f['affiliate']])
        ->postJson($uri, $payload)->assertOk()->assertJsonPath('message', 'Insufficient wallet balance');
})->with([
    'cable legacy fallback' => ['cable_subscription', '/user/cable_subscription/store', [
        'smart_card_number' => '1234567890', 'validation_customer_name' => 'Verified Customer',
    ]],
    'electricity legacy fallback' => ['utility_bills', '/user/electricity/store', [
        'metre_number' => '01234567890', 'validation_extra_info' => 'Verified Customer',
        'validation_address' => 'Lagos', 'amount' => 1000,
    ]],
]);
