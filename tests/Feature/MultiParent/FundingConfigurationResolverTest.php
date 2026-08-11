<?php

use App\Models\Affiliate;
use App\Models\AffiliateFundingProviderConfig;
use App\Models\FundingProvider;
use App\Models\ParentBusiness;
use App\Models\ParentFundingProvider;
use App\Services\Funding\FundingConfigurationResolver;
use App\Services\Funding\FundingWebhookRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function resolvableFunding(): array
{
    $parent = ParentBusiness::create(['name' => 'Resolver Parent', 'slug' => 'resolver-funding']);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id, 'name' => 'Resolver Child', 'slug' => 'resolver-child', 'affiliate_plan_id' => 1, 'ip_address' => '127.11.0.1', 'contact_phone' => '08110000001', 'contact_email' => 'resolver@example.test', 'parent_key' => 'resolver-key', 'parent_email' => 'resolver-parent@example.test']);
    $provider = FundingProvider::create(['name' => 'Xixapay', 'slug' => 'xixapay', 'adapter_key' => 'xixapay', 'credential_fields' => ['api_key'], 'active' => true]);
    $parentProvider = ParentFundingProvider::create(['parent_business_id' => $parent->id, 'funding_provider_id' => $provider->id, 'credentials' => ['api_key' => 'parent-key'], 'active' => true, 'generation_enabled' => true]);
    $config = AffiliateFundingProviderConfig::create(['affiliate_id' => $affiliate->id, 'parent_funding_provider_id' => $parentProvider->id, 'management_mode' => 'parent_managed', 'active' => true, 'generation_enabled' => true]);

    return compact('parent', 'affiliate', 'provider', 'parentProvider', 'config');
}

it('keeps legacy funding authoritative until the feature flag is enabled', function () {
    $fixture = resolvableFunding();
    config(['parent_businesses.features.multi_parent_funding' => false]);

    expect(app(FundingConfigurationResolver::class)->resolveForGeneration($fixture['affiliate'], 'xixapay'))->toBeNull();

    config(['parent_businesses.features.multi_parent_funding' => true]);
    $resolved = app(FundingConfigurationResolver::class)->resolveForGeneration($fixture['affiliate'], 'xixapay');
    expect($resolved['credentials'])->toBe(['api_key' => 'parent-key'])
        ->and($resolved['management_mode'])->toBe('parent_managed');
});

it('blocks new generation when disabled but records duplicate webhooks only once', function () {
    $fixture = resolvableFunding();
    config(['parent_businesses.features.multi_parent_funding' => true]);
    $fixture['config']->update(['generation_enabled' => false]);

    expect(fn () => app(FundingConfigurationResolver::class)->resolveForGeneration($fixture['affiliate'], 'xixapay'))->toThrow(ValidationException::class);

    $recorder = app(FundingWebhookRecorder::class);
    $first = $recorder->record($fixture['provider'], 'evt-one', ['amount' => 500], $fixture['config']);
    $second = $recorder->record($fixture['provider'], 'evt-one', ['amount' => 500], $fixture['config']);
    expect($first['duplicate'])->toBeFalse()->and($second['duplicate'])->toBeTrue()->and($first['event']->is($second['event']))->toBeTrue();
});
