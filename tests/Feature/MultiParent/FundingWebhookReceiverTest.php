<?php

use App\Models\Affiliate;
use App\Models\AffiliateFundingProviderConfig;
use App\Models\FundingProvider;
use App\Models\ParentBusiness;
use App\Models\ParentFundingProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function webhookFundingFixture(): array
{
    $parent = ParentBusiness::create(['name' => 'Webhook Parent', 'slug' => 'webhook-parent']);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id, 'name' => 'Webhook Child', 'slug' => 'webhook-child', 'affiliate_plan_id' => 1, 'ip_address' => '127.12.0.1', 'contact_phone' => '08120000001', 'contact_email' => 'webhook@example.test', 'parent_key' => 'webhook-key', 'parent_email' => 'webhook-parent@example.test']);
    $provider = FundingProvider::create(['name' => 'Xixapay', 'slug' => 'xixapay', 'adapter_key' => 'xixapay', 'credential_fields' => ['api_key'], 'active' => true]);
    $parentProvider = ParentFundingProvider::create(['parent_business_id' => $parent->id, 'funding_provider_id' => $provider->id, 'credentials' => ['api_key' => 'parent-key'], 'active' => true, 'generation_enabled' => true]);
    $config = AffiliateFundingProviderConfig::create(['affiliate_id' => $affiliate->id, 'parent_funding_provider_id' => $parentProvider->id, 'management_mode' => 'affiliate_managed', 'credentials' => ['api_key' => 'affiliate-key'], 'active' => true, 'generation_enabled' => true]);

    return compact('parent', 'affiliate', 'provider', 'parentProvider', 'config');
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
