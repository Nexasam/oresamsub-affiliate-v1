<?php

use App\Models\Admin;
use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\ProviderAdapter;
use App\Models\ProviderConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function legacyPromotionReviewer(): Admin
{
    return Admin::create([
        'name' => 'Legacy promotion reviewer',
        'email' => 'legacy-promotion@example.test',
        'password' => 'password',
        'active' => true,
    ]);
}

function legacyParentConnection(array $shared = [], array $parent = []): ParentProviderConnection
{
    $business = ParentBusiness::create(['name' => 'Legacy Parent', 'slug' => 'legacy-parent']);
    $provider = ProviderConnection::create([
        'name' => 'Legacy Provider',
        'slug' => 'legacy-provider',
        'adapter' => 'configurable_http',
        'settings' => $shared,
        'status' => 'active',
    ]);

    return ParentProviderConnection::create([
        'parent_business_id' => $business->id,
        'provider_connection_id' => $provider->id,
        'name' => 'Legacy primary',
        'base_url' => 'https://legacy-provider.test/api',
        'credentials' => ['api_public_key' => 'never-promote-this-secret'],
        'settings' => $parent ?: [
            'http_method' => 'POST',
            'timeout_seconds' => 30,
            'endpoints' => ['data' => 'https://legacy-provider.test/api/data'],
            'request_headers' => [['key' => 'Authorization', 'type' => 'credential', 'value' => 'api_public_key']],
            'success_conditions' => [['key' => 'success', 'value' => 'true']],
            'is_primary' => true,
        ],
        'status' => 'active',
        'approval_status' => 'approved',
    ]);
}

it('promotes legacy technical settings into an unused shared connection without moving credentials', function () {
    $parentConnection = legacyParentConnection();

    $this->actingAs(legacyPromotionReviewer(), 'platform_admin')
        ->postJson("/admin/provider-connections/{$parentConnection->id}/promote-legacy-configuration", [
            'promote_to_adapter' => false,
        ])
        ->assertOk()
        ->assertJsonPath('promotion.strategy', 'updated_in_place')
        ->assertJsonPath('connection.provider_connection.id', $parentConnection->provider_connection_id);

    $shared = $parentConnection->providerConnection()->first();
    expect($shared->base_url)->toBe('https://legacy-provider.test/api')
        ->and($shared->settings['endpoints']['data'])->toBe('https://legacy-provider.test/api/data')
        ->and($shared->settings)->not->toHaveKey('is_primary')
        ->and(json_encode($shared->settings))->not->toContain('never-promote-this-secret')
        ->and($parentConnection->fresh()->credentials['api_public_key'])->toBe('never-promote-this-secret')
        ->and($parentConnection->fresh()->settings['is_primary'])->toBeTrue()
        ->and(DB::table('provider_configuration_promotions')->where('parent_provider_connection_id', $parentConnection->id)->count())->toBe(1)
        ->and(DB::table('provider_configuration_promotions')->value('source_snapshot'))->not->toContain('never-promote-this-secret');
});

it('clones the shared connection before promotion when another parent already uses it', function () {
    $source = legacyParentConnection(['http_method' => 'GET']);
    $otherParent = ParentBusiness::create(['name' => 'Other Parent', 'slug' => 'other-parent']);
    ParentProviderConnection::create([
        'parent_business_id' => $otherParent->id,
        'provider_connection_id' => $source->provider_connection_id,
        'name' => 'Other parent connection',
        'settings' => ['is_primary' => true],
        'status' => 'active',
        'approval_status' => 'approved',
    ]);

    $this->actingAs(legacyPromotionReviewer(), 'platform_admin')
        ->postJson("/admin/provider-connections/{$source->id}/promote-legacy-configuration", [
            'promote_to_adapter' => false,
        ])
        ->assertOk()
        ->assertJsonPath('promotion.strategy', 'cloned');

    $source->refresh();
    expect(ProviderConnection::count())->toBe(2)
        ->and($source->provider_connection_id)->not->toBeNull()
        ->and($source->provider_connection_id)->not->toBe(1)
        ->and($source->providerConnection->settings['http_method'])->toBe('POST')
        ->and(ParentProviderConnection::where('parent_business_id', $otherParent->id)->value('provider_connection_id'))->toBe(1);
});

it('can optionally create a reusable adapter from promoted legacy configuration', function () {
    $source = legacyParentConnection();

    $this->actingAs(legacyPromotionReviewer(), 'platform_admin')
        ->postJson("/admin/provider-connections/{$source->id}/promote-legacy-configuration", [
            'promote_to_adapter' => true,
        ])
        ->assertOk()
        ->assertJsonPath('promotion.adapter_created', true);

    $source->refresh();
    $adapter = ProviderAdapter::findOrFail($source->provider_adapter_id);
    expect($source->providerConnection->provider_adapter_id)->toBe($adapter->id)
        ->and($adapter->settings['http_method'])->toBe('POST')
        ->and($adapter->settings)->not->toHaveKey('endpoints')
        ->and(json_encode($adapter->settings))->not->toContain('legacy-provider.test');
});

it('protects legacy configuration promotion with the platform admin guard', function () {
    $source = legacyParentConnection();

    $this->postJson("/admin/provider-connections/{$source->id}/promote-legacy-configuration", [
        'promote_to_adapter' => false,
    ])->assertUnauthorized();
});
