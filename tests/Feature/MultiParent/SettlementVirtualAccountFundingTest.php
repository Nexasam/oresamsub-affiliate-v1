<?php

use App\Models\Affiliate;
use App\Models\AffiliateSettlementVirtualAccount;
use App\Models\FundingProvider;
use App\Models\ParentBusiness;
use App\Models\ParentFundingProvider;
use App\Models\Role;
use App\Models\User;
use App\Services\Funding\SettlementVirtualAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function settlementFundingFixture(): array
{
    $parent = ParentBusiness::create(['name' => 'Settlement Parent', 'slug' => 'settlement-parent']);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create([
        'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id,
        'name' => 'Settlement Child', 'slug' => 'settlement-child', 'affiliate_plan_id' => 1,
        'ip_address' => '127.20.0.1', 'contact_phone' => '08130000001',
        'contact_email' => 'settlement-child@example.test', 'parent_key' => 'settlement-key',
        'parent_email' => 'parent@example.test',
    ]);
    $provider = FundingProvider::create(['name' => 'SecurewaveNG', 'slug' => 'securewaveng', 'adapter_key' => 'securewaveng', 'active' => true]);
    $parentProvider = ParentFundingProvider::create([
        'parent_business_id' => $parent->id, 'funding_provider_id' => $provider->id,
        'credentials' => ['api_public_key' => 'parent-public', 'api_secret_key' => 'parent-secret', 'business_id' => 'parent-business', 'biz_bvn' => '22222222222'],
        'webhook_key' => 'parent-settlement-hook', 'webhook_secret' => 'parent-hook-secret',
        'webhook_active' => true, 'active' => true, 'generation_enabled' => true,
    ]);
    $parentProvider->banks()->create(['name' => 'Wema', 'bank_code' => 'WEMA', 'rate_type' => 'flat', 'rate_value' => 25, 'active' => true, 'generation_enabled' => true]);

    return compact('parent', 'affiliate', 'provider', 'parentProvider');
}

it('generates an affiliate settlement virtual account with parent credentials exactly once', function () {
    $f = settlementFundingFixture();
    Http::fake(['securewaveng.com/*' => Http::response(['status' => true, 'data' => [[
        'status' => 1, 'account_reference' => 'SETTLEMENT-VA-1', 'account_bank' => 'Wema',
        'bank_code' => 'WEMA', 'account_name' => 'Settlement Child', 'account_number' => '0999999999',
    ]]], 200)]);
    config(['parent_businesses.features.multi_parent_funding' => true]);

    $service = app(SettlementVirtualAccountService::class);
    expect($service->generate($f['affiliate'], $f['parentProvider'])['status'])->toBe(1)
        ->and($service->generate($f['affiliate'], $f['parentProvider'])['status'])->toBe(1);

    $this->assertDatabaseHas('affiliate_settlement_virtual_accounts', [
        'affiliate_id' => $f['affiliate']->id, 'parent_funding_provider_id' => $f['parentProvider']->id,
        'account_reference' => 'SETTLEMENT-VA-1', 'account_number' => '0999999999', 'wallet_purpose' => 'settlement',
    ]);
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->hasHeader('x-api-key', 'parent-public') && $request['business_id'] === 'parent-business');
});

it('allows only the affiliate admin to view and generate its settlement accounts', function () {
    $f = settlementFundingFixture();
    $userPlanId = DB::table('affiliate_user_plans')->insertGetId(['affiliate_id' => $f['affiliate']->id, 'user_plan_name' => 'Basic', 'plan_level' => '1', 'is_default' => '1', 'visibility' => '1', 'created_at' => now(), 'updated_at' => now()]);
    $adminRole = Role::create(['role_name' => 'Admin']);
    $admin = User::factory()->create(['affiliate_id' => $f['affiliate']->id, 'user_plan_id' => $userPlanId, 'role_id' => $adminRole->id]);
    Http::fake(['securewaveng.com/*' => Http::response(['status' => true, 'data' => [[
        'status' => 1, 'account_reference' => 'SETTLEMENT-VA-UI', 'account_bank' => 'Wema',
        'bank_code' => 'WEMA', 'account_name' => 'Settlement Child', 'account_number' => '0888888888',
    ]]], 200)]);
    config(['parent_businesses.features.multi_parent_funding' => true]);

    $this->actingAs($admin)->withSession(['affiliate' => $f['affiliate']])
        ->get('/admin/settlement-funding')->assertOk()->assertSee('Settlement funding');
    $this->actingAs($admin)->withSession(['affiliate' => $f['affiliate']])
        ->post("/admin/settlement-funding/providers/{$f['parentProvider']->id}/generate")
        ->assertRedirect('/admin/settlement-funding');
    $this->assertDatabaseHas('affiliate_settlement_virtual_accounts', ['affiliate_id' => $f['affiliate']->id, 'account_number' => '0888888888']);
});

it('shows the affiliate settlement balance and business account on the admin dashboard', function () {
    $f = settlementFundingFixture();
    $userPlanId = DB::table('affiliate_user_plans')->insertGetId(['affiliate_id' => $f['affiliate']->id, 'user_plan_name' => 'Basic', 'plan_level' => '1', 'is_default' => '1', 'visibility' => '1', 'created_at' => now(), 'updated_at' => now()]);
    $adminRole = Role::create(['role_name' => 'Admin']);
    $admin = User::factory()->create(['affiliate_id' => $f['affiliate']->id, 'user_plan_id' => $userPlanId, 'role_id' => $adminRole->id]);
    $f['affiliate']->settlementWallet()->create(['parent_business_id' => $f['parent']->id, 'available_balance' => '975.00', 'reserved_balance' => '25.00', 'currency' => 'NGN', 'status' => 'active']);
    AffiliateSettlementVirtualAccount::create([
        'parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id,
        'parent_funding_provider_id' => $f['parentProvider']->id, 'wallet_purpose' => 'settlement',
        'bank_name' => 'Wema', 'bank_code' => 'WEMA', 'account_name' => 'Settlement Child',
        'account_number' => '0666666666', 'account_reference' => 'SETTLEMENT-DASHBOARD', 'status' => 'active',
    ]);
    config(['parent_businesses.features.multi_parent_funding' => true]);

    $this->actingAs($admin)->withSession(['affiliate' => $f['affiliate']])
        ->get('/dashboard')->assertOk()
        ->assertSee('Settlement wallet')->assertSee('₦975.00')->assertSee('0666666666');
});

it('shows recent settlement funding transactions on the affiliate admin dashboard', function () {
    $f = settlementFundingFixture();
    $userPlanId = DB::table('affiliate_user_plans')->insertGetId(['affiliate_id' => $f['affiliate']->id, 'user_plan_name' => 'Basic', 'plan_level' => '1', 'is_default' => '1', 'visibility' => '1', 'created_at' => now(), 'updated_at' => now()]);
    $adminRole = Role::create(['role_name' => 'Admin']);
    $admin = User::factory()->create(['affiliate_id' => $f['affiliate']->id, 'user_plan_id' => $userPlanId, 'role_id' => $adminRole->id]);
    $wallet = $f['affiliate']->settlementWallet()->create(['parent_business_id' => $f['parent']->id, 'available_balance' => '975.00', 'reserved_balance' => '0.00', 'currency' => 'NGN', 'status' => 'active']);
    $wallet->ledgerEntries()->create([
        'parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id,
        'entry_type' => 'settlement_funding', 'amount' => '975.00', 'balance_before' => '0.00',
        'balance_after' => '975.00', 'reference' => 'FUNDING:SETTLEMENT-PAY-DASHBOARD',
        'actor_type' => 'funding_webhook', 'actor_id' => 0, 'reason' => 'Automated settlement funding',
        'metadata' => ['gross_amount' => '1000.00', 'charge' => '25.00', 'external_event_id' => 'SETTLEMENT-PAY-DASHBOARD'],
    ]);
    config(['parent_businesses.features.multi_parent_funding' => true]);

    $this->actingAs($admin)->withSession(['affiliate' => $f['affiliate']])
        ->get('/dashboard')->assertOk()->assertSee('Recent settlement funding')
        ->assertSee('SETTLEMENT-PAY-DASHBOARD')->assertSee('₦1,000.00')->assertSee('₦25.00')->assertSee('₦975.00');
});

it('returns the affiliate to settlement funding with a safe provider error', function () {
    $f = settlementFundingFixture();
    $userPlanId = DB::table('affiliate_user_plans')->insertGetId(['affiliate_id' => $f['affiliate']->id, 'user_plan_name' => 'Basic', 'plan_level' => '1', 'is_default' => '1', 'visibility' => '1', 'created_at' => now(), 'updated_at' => now()]);
    $adminRole = Role::create(['role_name' => 'Admin']);
    $admin = User::factory()->create(['affiliate_id' => $f['affiliate']->id, 'user_plan_id' => $userPlanId, 'role_id' => $adminRole->id]);
    Http::fake(['securewaveng.com/*' => Http::response(['status' => false, 'message' => 'Invalid API Credentials', 'data' => []], 404)]);
    config(['parent_businesses.features.multi_parent_funding' => true]);

    $this->actingAs($admin)->withSession(['affiliate' => $f['affiliate']])
        ->post("/admin/settlement-funding/providers/{$f['parentProvider']->id}/generate")
        ->assertRedirect('/admin/settlement-funding')
        ->assertSessionHas('error', 'SecurewaveNG rejected the parent funding credentials. Ask your parent administrator to verify the API public key, API secret key and business ID.');
});

it('credits the matching affiliate settlement wallet once from a signed parent webhook', function () {
    $f = settlementFundingFixture();
    AffiliateSettlementVirtualAccount::create([
        'parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id,
        'parent_funding_provider_id' => $f['parentProvider']->id, 'wallet_purpose' => 'settlement',
        'bank_name' => 'Wema', 'bank_code' => 'WEMA', 'account_name' => 'Settlement Child',
        'account_number' => '0999999999', 'account_reference' => 'SETTLEMENT-VA-1', 'status' => 'active',
    ]);
    $payload = json_encode([
        'transaction_status' => 'success', 'provider_reference' => 'SETTLEMENT-PAY-1',
        'amount' => 1000, 'settlement_amount' => 975,
        'customer' => ['email' => 'untrusted@example.test'],
        'receiver' => ['bank' => 'Wema', 'account_number' => '0999999999'],
    ]);
    config(['parent_businesses.features.multi_parent_funding' => true]);
    $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => hash_hmac('sha256', $payload, 'parent-secret')];

    $this->call('POST', '/api/funding/webhooks/securewaveng/parent-settlement-hook', [], [], [], $server, $payload)
        ->assertOk()->assertJson(['status' => 'processed']);
    $this->call('POST', '/api/funding/webhooks/securewaveng/parent-settlement-hook', [], [], [], $server, $payload)
        ->assertOk()->assertJson(['duplicate' => true]);

    $wallet = $f['affiliate']->fresh()->settlementWallet;
    expect($wallet->available_balance)->toBe('975.00')
        ->and($wallet->ledgerEntries()->where('entry_type', 'settlement_funding')->count())->toBe(1);
});

it('never guesses a settlement account when the webhook omits account identity', function () {
    $f = settlementFundingFixture();
    AffiliateSettlementVirtualAccount::create([
        'parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id,
        'parent_funding_provider_id' => $f['parentProvider']->id, 'wallet_purpose' => 'settlement',
        'bank_name' => 'Wema', 'bank_code' => 'WEMA', 'account_name' => 'Settlement Child',
        'account_number' => '0777777777', 'account_reference' => 'SETTLEMENT-VA-SAFE', 'status' => 'active',
    ]);
    $payload = json_encode([
        'transaction_status' => 'success', 'provider_reference' => 'SETTLEMENT-UNKNOWN-1',
        'amount' => 1000, 'settlement_amount' => 975, 'receiver' => ['bank' => 'Wema'],
    ]);
    config(['parent_businesses.features.multi_parent_funding' => true]);

    $this->call('POST', '/api/funding/webhooks/securewaveng/parent-settlement-hook', [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => hash_hmac('sha256', $payload, 'parent-secret'),
    ], $payload)->assertStatus(202)->assertJson(['status' => 'unresolved']);

    expect($f['affiliate']->fresh()->settlementWallet)->toBeNull();
});
