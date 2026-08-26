<?php

use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\ProviderAdapter;
use App\Models\ProviderConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets a parent select an existing provider and submit only credentials', function () {
    $parent = ParentBusiness::create(['name' => 'Simple Parent', 'slug' => 'simple-parent']);
    $admin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => 'simple@example.test', 'password' => 'password', 'active' => true]);
    $adapter = ProviderAdapter::create(['name' => 'MSORG', 'slug' => 'msorg-simple', 'adapter_key' => 'msorg_simple', 'capabilities' => ['services' => ['data'], 'credential_fields' => ['api_public_key', 'api_password']], 'status' => 'active']);
    $provider = ProviderConnection::create(['provider_adapter_id' => $adapter->id, 'name' => 'PaulTechs', 'slug' => 'paultechs-simple', 'adapter' => 'msorg_simple', 'status' => 'active']);

    $this->actingAs($admin, 'parent_admin')->post('/parent-admin/provider-connections', [
        'provider_adapter_id' => $adapter->id, 'provider_connection_id' => $provider->id,
        'name' => 'PaulTechs Primary', 'credentials' => ['api_public_key' => 'token', 'api_password' => 'password'],
        'status' => 'active', 'is_primary' => 1,
    ])->assertRedirect('/parent-admin/provider-connections');

    $saved = ParentProviderConnection::sole();
    expect($saved->provider_adapter_id)->toBe($adapter->id)
        ->and($saved->provider_connection_id)->toBe($provider->id)
        ->and($saved->request_type)->toBe('existing')
        ->and($saved->settings)->toBe(['is_primary' => true])
        ->and($saved->approval_status)->toBe('pending');
});

it('lets a parent propose an unlisted website under an adapter', function () {
    $parent = ParentBusiness::create(['name' => 'Discovery Parent', 'slug' => 'discovery-parent']);
    $admin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => 'discovery@example.test', 'password' => 'password', 'active' => true]);
    $adapter = ProviderAdapter::create(['name' => 'MSORG', 'slug' => 'msorg-discovery', 'adapter_key' => 'msorg_discovery', 'capabilities' => ['credential_fields' => ['api_public_key']], 'status' => 'active']);

    $this->actingAs($admin, 'parent_admin')->post('/parent-admin/provider-connections', [
        'provider_adapter_id' => $adapter->id, 'provider_connection_id' => null,
        'name' => 'Unknown Provider', 'proposed_provider_name' => 'Unknown Provider',
        'proposed_base_url' => 'https://unknown-provider.test', 'discovery_notes' => 'This website uses MSORG.',
        'credentials' => ['api_public_key' => 'token'], 'status' => 'active', 'is_primary' => 0,
    ])->assertRedirect('/parent-admin/provider-connections');

    expect(ParentProviderConnection::sole()->request_type)->toBe('discovery')
        ->and(ParentProviderConnection::sole()->provider_connection_id)->toBeNull()
        ->and(ParentProviderConnection::sole()->proposed_provider_name)->toBe('Unknown Provider');
});
