<?php

use App\Models\Affiliate;
use App\Models\AffiliateFundingProviderConfig;
use App\Models\FundingProvider;
use App\Models\FundingWebhookEvent;
use App\Models\ParentBusiness;
use App\Models\ParentFundingProvider;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates the additive multi-parent funding foundation', function () {
    expect(Schema::hasColumns('funding_providers', ['name', 'slug', 'adapter_key', 'credential_fields', 'active']))->toBeTrue()
        ->and(Schema::hasColumns('parent_funding_providers', ['parent_business_id', 'funding_provider_id', 'credentials', 'active', 'generation_enabled']))->toBeTrue()
        ->and(Schema::hasColumns('affiliate_funding_provider_configs', ['affiliate_id', 'parent_funding_provider_id', 'management_mode', 'credentials', 'bank_codes', 'active', 'generation_enabled']))->toBeTrue()
        ->and(Schema::hasColumns('parent_funding_provider_banks', ['parent_funding_provider_id', 'name', 'bank_code', 'rate_type', 'rate_value', 'percentage_cap', 'active', 'generation_enabled']))->toBeTrue()
        ->and(Schema::hasColumns('affiliate_funding_provider_banks', ['affiliate_funding_provider_config_id', 'parent_funding_provider_bank_id', 'rate_type', 'rate_value', 'percentage_cap', 'active', 'generation_enabled']))->toBeTrue()
        ->and(Schema::hasColumns('parent_funding_providers', ['webhook_key', 'webhook_secret', 'webhook_active']))->toBeTrue()
        ->and(Schema::hasColumns('affiliate_funding_provider_configs', ['webhook_key', 'webhook_secret', 'webhook_active']))->toBeTrue()
        ->and(Schema::hasColumns('funding_mode_change_requests', ['affiliate_funding_provider_config_id', 'requested_mode', 'status', 'reviewed_by_parent_admin_id']))->toBeTrue()
        ->and(Schema::hasColumns('funding_webhook_events', ['funding_provider_id', 'external_event_id', 'payload', 'status']))->toBeTrue()
        ->and(Schema::hasColumns('user_virtual_accounts', ['parent_business_id', 'parent_funding_provider_id', 'affiliate_funding_provider_config_id']))->toBeTrue();
});

it('encrypts funding credentials and prevents duplicate provider webhook events', function () {
    $parent = ParentBusiness::create(['name' => 'Funding Parent', 'slug' => 'funding-parent']);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create([
        'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id,
        'name' => 'Funding Affiliate', 'slug' => 'funding-affiliate', 'affiliate_plan_id' => 1,
        'ip_address' => '127.8.0.1', 'contact_phone' => '08080000001', 'contact_email' => 'funding@example.test',
        'parent_key' => 'funding-key', 'parent_email' => 'funding-parent@example.test',
    ]);
    $provider = FundingProvider::create(['name' => 'Xixapay', 'slug' => 'xixapay', 'adapter_key' => 'xixapay', 'credential_fields' => ['api_key'], 'active' => true]);
    $parentProvider = ParentFundingProvider::create(['parent_business_id' => $parent->id, 'funding_provider_id' => $provider->id, 'credentials' => ['api_key' => 'parent-secret'], 'active' => true, 'generation_enabled' => true]);
    $affiliateConfig = AffiliateFundingProviderConfig::create(['affiliate_id' => $affiliate->id, 'parent_funding_provider_id' => $parentProvider->id, 'management_mode' => 'affiliate_managed', 'credentials' => ['api_key' => 'affiliate-secret'], 'active' => true, 'generation_enabled' => true]);

    expect(DB::table('parent_funding_providers')->where('id', $parentProvider->id)->value('credentials'))->not->toContain('parent-secret')
        ->and(DB::table('affiliate_funding_provider_configs')->where('id', $affiliateConfig->id)->value('credentials'))->not->toContain('affiliate-secret')
        ->and($parentProvider->toArray())->not->toHaveKey('credentials')
        ->and($affiliateConfig->toArray())->not->toHaveKey('credentials');

    FundingWebhookEvent::create(['funding_provider_id' => $provider->id, 'external_event_id' => 'evt-100', 'payload' => ['amount' => 100], 'status' => 'received']);
    expect(fn () => FundingWebhookEvent::create(['funding_provider_id' => $provider->id, 'external_event_id' => 'evt-100', 'payload' => [], 'status' => 'received']))->toThrow(UniqueConstraintViolationException::class);
});
