<?php

use App\Models\Affiliate;
use App\Models\AffiliateFundingProviderConfig;
use App\Models\FundingProvider;
use App\Models\ParentBusiness;
use App\Models\ParentFundingProvider;
use App\Models\User;
use App\Services\Funding\MultiParentVirtualAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function virtualAccountFixture(string $slug): array
{
    $parent = ParentBusiness::create(['name' => 'VA Parent', 'slug' => "va-parent-{$slug}"]);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id, 'name' => 'VA Child', 'slug' => "va-child-{$slug}", 'affiliate_plan_id' => 1, 'ip_address' => '127.13.0.1', 'contact_phone' => '08130000001', 'contact_email' => "va-{$slug}@example.test", 'parent_key' => "va-{$slug}-key", 'parent_email' => 'va-parent@example.test']);
    $userPlanId = DB::table('affiliate_user_plans')->insertGetId(['affiliate_id' => $affiliate->id, 'user_plan_name' => 'Basic', 'plan_level' => '1', 'is_default' => '1', 'visibility' => '1', 'created_at' => now(), 'updated_at' => now()]);
    $provider = FundingProvider::create(['name' => str($slug)->headline(), 'slug' => $slug, 'adapter_key' => $slug, 'active' => true]);
    $parentProvider = ParentFundingProvider::create(['parent_business_id' => $parent->id, 'funding_provider_id' => $provider->id, 'credentials' => ['api_public_key' => 'public-key', 'api_secret_key' => 'secret-key', 'contract_code' => 'business-1', 'biz_bvn' => '22222222222'], 'active' => true, 'generation_enabled' => true]);
    $config = AffiliateFundingProviderConfig::create(['affiliate_id' => $affiliate->id, 'parent_funding_provider_id' => $parentProvider->id, 'management_mode' => 'affiliate_managed', 'credentials' => ['api_public_key' => 'public-key', 'api_secret_key' => 'secret-key', 'contract_code' => 'business-1', 'biz_bvn' => '22222222222'], 'active' => true, 'generation_enabled' => true]);
    $parentBank = $parentProvider->banks()->create(['name' => 'Wema', 'bank_code' => 'WEMA', 'rate_type' => 'flat', 'rate_value' => 25, 'active' => true, 'generation_enabled' => true]);
    $config->banks()->create(['parent_funding_provider_bank_id' => $parentBank->id, 'rate_type' => 'flat', 'rate_value' => 25, 'active' => true, 'generation_enabled' => true]);
    $user = User::factory()->create(['affiliate_id' => $affiliate->id, 'user_plan_id' => $userPlanId, 'email' => "customer-{$slug}@example.test", 'bvn' => '11111111111']);

    return compact('parent', 'affiliate', 'provider', 'parentProvider', 'config', 'user');
}

it('generates and stores xixapay virtual accounts with affiliate credentials', function () {
    $fixture = virtualAccountFixture('xixapay');
    Http::fake(['api.xixapay.com/*' => Http::response(['status' => 'success', 'bankAccounts' => [[
        'bankName' => 'Wema', 'bankCode' => 'WEMA', 'accountName' => 'Test User', 'accountNumber' => '0123456789', 'Reserved_Account_Id' => 'XIXA-VA-1',
    ]]], 200)]);
    config(['parent_businesses.features.multi_parent_funding' => true]);

    $result = app(MultiParentVirtualAccountService::class)->generateForUser($fixture['user']);

    expect($result['status'])->toBe(1);
    $this->assertDatabaseHas('user_virtual_accounts', ['affiliate_id' => $fixture['affiliate']->id, 'user_id' => $fixture['user']->id, 'affiliate_funding_provider_config_id' => $fixture['config']->id, 'account_reference' => 'XIXA-VA-1']);
    Http::assertSent(fn ($request) => $request->hasHeader('api-key', 'public-key') && $request['bankCode'] === ['WEMA']);
});

it('generates and stores securewaveng virtual accounts once', function () {
    $fixture = virtualAccountFixture('securewaveng');
    Http::fake(['securewaveng.com/*' => Http::response(['status' => true, 'data' => [[
        'status' => 1, 'account_reference' => 'SW-VA-1', 'account_bank' => 'Wema', 'bank_code' => 'WEMA', 'account_name' => 'Test User', 'account_email' => $fixture['user']->email, 'account_number' => '9876543210',
    ]]], 200)]);
    config(['parent_businesses.features.multi_parent_funding' => true]);

    $service = app(MultiParentVirtualAccountService::class);
    expect($service->generateForUser($fixture['user'])['status'])->toBe(1)
        ->and($service->generateForUser($fixture['user'])['status'])->toBe(1);

    $this->assertDatabaseCount('user_virtual_accounts', 1);
    Http::assertSentCount(1);
});

it('normalizes a customer phone number before sending it to securewaveng', function () {
    $fixture = virtualAccountFixture('securewaveng');
    $fixture['user']->update(['phone_number' => '+234 (816) 850-9044']);
    Http::fake(['securewaveng.com/*' => Http::response(['status' => true, 'data' => [[
        'status' => 1, 'account_reference' => 'SW-NORMALIZED-PHONE', 'account_bank' => 'Wema',
        'bank_code' => 'WEMA', 'account_name' => 'Test User', 'account_number' => '9876543212',
    ]]], 200)]);
    config(['parent_businesses.features.multi_parent_funding' => true]);

    expect(app(MultiParentVirtualAccountService::class)->generateForUser($fixture['user'])['status'])->toBe(1);

    Http::assertSent(fn ($request) => $request['phone_number'] === '2348168509044');
});

it('does not expose securewaveng validation details to the customer', function () {
    $fixture = virtualAccountFixture('securewaveng');
    Http::fake(['securewaveng.com/*' => Http::response([
        'message' => 'The phone number field must be between 10 and 14 digits.',
        'errors' => ['phone_number' => ['The phone number field must be between 10 and 14 digits.']],
    ], 422)]);
    config(['parent_businesses.features.multi_parent_funding' => true]);

    $result = app(MultiParentVirtualAccountService::class)->generateForUser($fixture['user']);

    expect($result['status'])->toBe(-1)
        ->and($result['message'])->toContain('Please confirm your profile phone number')
        ->and($result['message'])->not->toContain('between 10 and 14 digits')
        ->and($result['message'])->not->toContain('HTTP request returned');
});

it('lets a customer generate only their own virtual account', function () {
    $fixture = virtualAccountFixture('securewaveng');
    $fixture['user']->forceFill(['email_verified_at' => now()])->save();
    $otherUser = User::factory()->create([
        'affiliate_id' => $fixture['affiliate']->id,
        'user_plan_id' => $fixture['user']->user_plan_id,
        'email' => 'other-va-customer@example.test',
    ]);
    Http::fake(['securewaveng.com/*' => Http::response(['status' => true, 'data' => [[
        'status' => 1, 'account_reference' => 'SELF-SERVICE-VA', 'account_bank' => 'Wema',
        'bank_code' => 'WEMA', 'account_name' => 'Authenticated Customer',
        'account_email' => $fixture['user']->email, 'account_number' => '9876543299',
    ]]], 200)]);
    config(['parent_businesses.features.multi_parent_funding' => true]);

    $this->withSession(['affiliate' => $fixture['affiliate']])
        ->actingAs($fixture['user'])
        ->post('/user/virtual_accounts/generate', ['user_id' => $otherUser->id])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('user_virtual_accounts', [
        'user_id' => $fixture['user']->id,
        'account_reference' => 'SELF-SERVICE-VA',
    ]);
    $this->assertDatabaseMissing('user_virtual_accounts', ['user_id' => $otherUser->id]);
});

it('uses securewaveng business_id credentials while retaining contract_code compatibility', function () {
    $fixture = virtualAccountFixture('securewaveng');
    $fixture['config']->update(['credentials' => [
        'api_public_key' => 'public-key', 'api_secret_key' => 'secret-key',
        'business_id' => 'securewave-business-9', 'biz_bvn' => '22222222222',
    ]]);
    Http::fake(['securewaveng.com/*' => Http::response(['status' => true, 'data' => [[
        'status' => 1, 'account_reference' => 'SW-VA-BUSINESS', 'account_bank' => 'Wema',
        'bank_code' => 'WEMA', 'account_name' => 'Test User', 'account_number' => '9876543211',
    ]]], 200)]);
    config(['parent_businesses.features.multi_parent_funding' => true]);

    expect(app(MultiParentVirtualAccountService::class)->generateForUser($fixture['user'])['status'])->toBe(1);
    Http::assertSent(fn ($request) => $request['business_id'] === 'securewave-business-9');
});

it('returns a safe diagnostic when securewaveng rejects affiliate credentials', function () {
    $fixture = virtualAccountFixture('securewaveng');
    Http::fake(['securewaveng.com/*' => Http::response('<!DOCTYPE html><title>Unauthorized</title>', 401)]);
    config(['parent_businesses.features.multi_parent_funding' => true]);

    $result = app(MultiParentVirtualAccountService::class)->generateForUser($fixture['user']);

    expect($result['status'])->toBe(-1)
        ->and($result['message'])->toContain('SecureWaveNG authentication failed')
        ->and($result['message'])->not->toContain('<!DOCTYPE html>');
});

it('presents normalized virtual account charges without requiring a legacy funding option', function () {
    $fixture = virtualAccountFixture('dashboard-charge');
    $account = $fixture['user']->virtual_accounts()->create([
        'affiliate_id' => $fixture['affiliate']->id,
        'parent_business_id' => $fixture['parent']->id,
        'parent_funding_provider_id' => $fixture['parentProvider']->id,
        'affiliate_funding_provider_config_id' => $fixture['config']->id,
        'funding_option_id' => null,
        'bank_name' => 'Wema',
        'bank_code' => 'WEMA',
        'account_name' => 'Test User',
        'account_number' => '0123456789',
    ]);

    expect($account->fundingChargeDetails())
        ->toMatchArray([
            'type' => 'flat',
            'value' => '25.00',
            'display' => '25.00 Naira flat',
        ]);
});

it('presents percentage virtual account charges with their naira cap', function () {
    $fixture = virtualAccountFixture('dashboard-percentage-charge');
    $fixture['config']->banks()->first()->update([
        'rate_type' => 'percentage',
        'rate_value' => 2,
        'percentage_cap' => 100,
    ]);
    $account = $fixture['user']->virtual_accounts()->create([
        'affiliate_id' => $fixture['affiliate']->id,
        'parent_business_id' => $fixture['parent']->id,
        'parent_funding_provider_id' => $fixture['parentProvider']->id,
        'affiliate_funding_provider_config_id' => $fixture['config']->id,
        'funding_option_id' => null,
        'bank_name' => 'Wema',
        'bank_code' => 'WEMA',
        'account_name' => 'Test User',
        'account_number' => '0123456791',
    ]);

    expect($account->fundingChargeDetails()['display'])->toBe('2.00% · capped at ₦100.00');
});

it('safely presents an account when neither normalized nor legacy charge configuration exists', function () {
    $fixture = virtualAccountFixture('dashboard-no-charge');
    $account = $fixture['user']->virtual_accounts()->create([
        'affiliate_id' => $fixture['affiliate']->id,
        'funding_option_id' => null,
        'bank_name' => 'Wema',
        'bank_code' => 'UNKNOWN',
        'account_name' => 'Test User',
        'account_number' => '0123456790',
    ]);

    expect($account->fundingChargeDetails())->toBeNull();
});
