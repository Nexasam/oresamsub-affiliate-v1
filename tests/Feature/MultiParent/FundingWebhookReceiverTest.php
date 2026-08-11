<?php

use App\Models\Affiliate;
use App\Models\AffiliateFundingProviderConfig;
use App\Models\FundingProvider;
use App\Models\ParentBusiness;
use App\Models\ParentFundingProvider;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function webhookFundingFixture(): array
{
    $parent = ParentBusiness::create(['name' => 'Webhook Parent', 'slug' => 'webhook-parent']);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id, 'name' => 'Webhook Child', 'slug' => 'webhook-child', 'affiliate_plan_id' => 1, 'ip_address' => '127.12.0.1', 'contact_phone' => '08120000001', 'contact_email' => 'webhook@example.test', 'parent_key' => 'webhook-key', 'parent_email' => 'webhook-parent@example.test']);
    $userPlanId = DB::table('affiliate_user_plans')->insertGetId(['affiliate_id' => $affiliate->id, 'user_plan_name' => 'Basic', 'plan_level' => '1', 'is_default' => '1', 'visibility' => '1', 'created_at' => now(), 'updated_at' => now()]);
    $provider = FundingProvider::create(['name' => 'Xixapay', 'slug' => 'xixapay', 'adapter_key' => 'xixapay', 'credential_fields' => ['api_key'], 'active' => true]);
    $parentProvider = ParentFundingProvider::create(['parent_business_id' => $parent->id, 'funding_provider_id' => $provider->id, 'credentials' => ['api_key' => 'parent-key'], 'active' => true, 'generation_enabled' => true]);
    $config = AffiliateFundingProviderConfig::create(['affiliate_id' => $affiliate->id, 'parent_funding_provider_id' => $parentProvider->id, 'management_mode' => 'affiliate_managed', 'credentials' => ['api_key' => 'affiliate-key'], 'active' => true, 'generation_enabled' => true]);

    return compact('parent', 'affiliate', 'provider', 'parentProvider', 'config', 'userPlanId');
}

it('keeps new webhooks disabled by default and records signed events idempotently when enabled', function () {
    $fixture = webhookFundingFixture();
    $fixture['config']->update(['management_mode' => 'affiliate_managed', 'webhook_key' => '019f73e0-10e0-7000-8000-000000000001', 'webhook_secret' => 'signing-secret', 'webhook_active' => true]);
    $path = '/api/funding/webhooks/xixapay/019f73e0-10e0-7000-8000-000000000001';
    $payload = json_encode(['reference' => 'funding-event-1', 'amount' => 500]);
    $signature = hash_hmac('sha256', $payload, 'signing-secret');

    config(['parent_businesses.features.multi_parent_funding' => false]);
    $this->call('POST', $path, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_WEBHOOK_SIGNATURE' => $signature, 'HTTP_X_WEBHOOK_ID' => 'funding-event-1'], $payload)->assertNotFound();

    config(['parent_businesses.features.multi_parent_funding' => true]);
    $this->call('POST', $path, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_WEBHOOK_SIGNATURE' => $signature, 'HTTP_X_WEBHOOK_ID' => 'funding-event-1'], $payload)
        ->assertStatus(202)->assertJson(['accepted' => true, 'duplicate' => false]);
    $this->call('POST', $path, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_WEBHOOK_SIGNATURE' => $signature, 'HTTP_X_WEBHOOK_ID' => 'funding-event-1'], $payload)
        ->assertStatus(202)->assertJson(['accepted' => true, 'duplicate' => true]);

    expect(AffiliateFundingProviderConfig::find($fixture['config']->id)->webhook_secret)->toBe('signing-secret');
});

it('credits an affiliate customer once from a signed xixapay webhook using affiliate bank pricing', function () {
    $fixture = webhookFundingFixture();
    $fixture['config']->update(['webhook_key' => 'xixa-hook', 'webhook_secret' => 'signing-secret', 'webhook_active' => true]);
    $parentBank = $fixture['parentProvider']->banks()->create(['name' => 'PalmPay', 'bank_code' => 'PALMPAY', 'rate_type' => 'flat', 'rate_value' => 50, 'active' => true, 'generation_enabled' => true]);
    $fixture['config']->banks()->create(['parent_funding_provider_bank_id' => $parentBank->id, 'rate_type' => 'percentage', 'rate_value' => 2, 'percentage_cap' => 100, 'active' => true, 'generation_enabled' => true]);
    $user = User::factory()->create(['affiliate_id' => $fixture['affiliate']->id, 'user_plan_id' => $fixture['userPlanId'], 'email' => 'payer@example.test', 'main_wallet' => '1000.00']);
    $payload = json_encode([
        'notification_status' => 'payment_successful', 'transaction_status' => 'success', 'transaction_id' => 'XIXA-1001',
        'amount_paid' => 5000, 'settlement_amount' => 4950, 'description' => 'Paid',
        'customer' => ['email' => 'payer@example.test'],
        'receiver' => ['bank' => 'PalmPay', 'name' => 'Payer', 'account_number' => '1234567890'],
    ]);
    $signature = hash_hmac('sha256', $payload, 'signing-secret');
    config(['parent_businesses.features.multi_parent_funding' => true]);

    $path = '/api/funding/webhooks/xixapay/xixa-hook';
    $this->call('POST', $path, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_XIXAPAY' => $signature], $payload)
        ->assertStatus(200)->assertJson(['accepted' => true, 'duplicate' => false, 'status' => 'processed']);
    $this->call('POST', $path, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_XIXAPAY' => $signature], $payload)
        ->assertStatus(200)->assertJson(['accepted' => true, 'duplicate' => true]);

    expect($user->fresh()->main_wallet)->toBe('5900.00');
    $this->assertDatabaseHas('wallet_logs', ['affiliate_id' => $fixture['affiliate']->id, 'user_id' => $user->id, 'transaction_id' => 'XIXA-1001', 'balance_before' => '1000.00', 'balance_after' => '5900.00']);
});

it('credits an affiliate customer from a signed securewaveng webhook', function () {
    $fixture = webhookFundingFixture();
    $fixture['provider']->update(['name' => 'SecurewaveNG', 'slug' => 'securewaveng', 'adapter_key' => 'securewaveng']);
    $fixture['config']->update(['webhook_key' => 'securewave-hook', 'webhook_secret' => 'secure-secret', 'webhook_active' => true]);
    $parentBank = $fixture['parentProvider']->banks()->create(['name' => 'Wema', 'bank_code' => 'WEMA', 'rate_type' => 'flat', 'rate_value' => 25, 'active' => true, 'generation_enabled' => true]);
    $fixture['config']->banks()->create(['parent_funding_provider_bank_id' => $parentBank->id, 'rate_type' => 'flat', 'rate_value' => 25, 'active' => true, 'generation_enabled' => true]);
    $user = User::factory()->create(['affiliate_id' => $fixture['affiliate']->id, 'user_plan_id' => $fixture['userPlanId'], 'email' => 'secure@example.test', 'main_wallet' => '200.00']);
    $payload = json_encode([
        'transaction_status' => 'success', 'provider_reference' => 'SW-1001', 'amount' => 1000,
        'settlement_amount' => 975, 'fees' => 25, 'customer' => ['email' => 'secure@example.test'],
        'receiver' => ['bank' => 'Wema', 'name' => 'Secure User', 'account_number' => '0123456789'],
    ]);
    $signature = hash_hmac('sha256', $payload, 'secure-secret');
    config(['parent_businesses.features.multi_parent_funding' => true]);

    $this->call('POST', '/api/funding/webhooks/securewaveng/securewave-hook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => $signature], $payload)
        ->assertStatus(200)->assertJson(['accepted' => true, 'duplicate' => false, 'status' => 'processed']);

    expect($user->fresh()->main_wallet)->toBe('1175.00');
});

it('holds oversized affiliate funding for approval instead of crediting immediately', function () {
    $fixture = webhookFundingFixture();
    $fixture['config']->update(['webhook_key' => 'limited-hook', 'webhook_secret' => 'limited-secret', 'webhook_active' => true]);
    Setting::withoutGlobalScope('affiliate')->create(['affiliate_id' => $fixture['affiliate']->id, 'field_name' => 'max_automatic_crediting_allowed', 'field_value' => '1000']);
    $user = User::factory()->create(['affiliate_id' => $fixture['affiliate']->id, 'user_plan_id' => $fixture['userPlanId'], 'email' => 'limited@example.test', 'main_wallet' => '200.00']);
    $payload = json_encode([
        'notification_status' => 'payment_successful', 'transaction_status' => 'success', 'transaction_id' => 'XIXA-LIMIT-1',
        'amount_paid' => 5000, 'settlement_amount' => 4950, 'customer' => ['email' => 'limited@example.test'],
        'receiver' => ['bank' => 'Unknown Bank'],
    ]);
    config(['parent_businesses.features.multi_parent_funding' => true]);

    $this->call('POST', '/api/funding/webhooks/xixapay/limited-hook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_XIXAPAY' => hash_hmac('sha256', $payload, 'limited-secret')], $payload)
        ->assertStatus(202)->assertJson(['status' => 'pending_review']);

    expect($user->fresh()->main_wallet)->toBe('200.00');
    $this->assertDatabaseHas('max_crystal_payments_pending_approvals', ['affiliate_id' => $fixture['affiliate']->id, 'user_id' => $user->id, 'payment_reference' => 'XIXA-LIMIT-1']);
});
