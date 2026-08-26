<?php

use App\Models\Admin;
use App\Models\ProviderAdapter;
use App\Models\ProviderConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a provider connection from an adapter snapshot with explicit overrides', function () {
    $admin = Admin::create(['name' => 'Platform', 'email' => 'catalogue@example.test', 'password' => 'password', 'active' => true]);
    $adapter = ProviderAdapter::create([
        'name' => 'MSORG', 'slug' => 'msorg', 'adapter_key' => 'configurable_http',
        'capabilities' => ['services' => ['data'], 'credential_fields' => ['api_public_key']],
        'settings' => ['http_method' => 'POST', 'timeout_seconds' => 30, 'endpoints' => ['data' => 'https://placeholder.test/api/data']],
        'version' => 3, 'status' => 'active',
    ]);

    $this->actingAs($admin, 'platform_admin')->postJson('/admin/provider-connections/catalogue', [
        'provider_adapter_id' => $adapter->id, 'name' => 'PaulTechs', 'slug' => 'paultechs',
        'base_url' => 'https://paultechs.test', 'website_url' => 'https://paultechs.test',
        'settings_overrides' => ['endpoints' => ['data' => 'https://paultechs.test/api/data']],
        'status' => 'active',
    ])->assertCreated()->assertJsonPath('connection.name', 'PaulTechs');

    $connection = ProviderConnection::where('slug', 'paultechs')->sole();
    expect($connection->provider_adapter_id)->toBe($adapter->id)
        ->and($connection->adapter_version)->toBe(3)
        ->and($connection->settings['http_method'])->toBe('POST')
        ->and($connection->settings['endpoints']['data'])->toBe('https://paultechs.test/api/data');

    $adapter->update(['settings' => ['http_method' => 'GET'], 'version' => 4]);
    expect($connection->fresh()->settings['http_method'])->toBe('POST');
});

it('rejects duplicate provider hosts within one adapter', function () {
    $admin = Admin::create(['name' => 'Platform', 'email' => 'catalogue-duplicate@example.test', 'password' => 'password', 'active' => true]);
    $adapter = ProviderAdapter::create(['name' => 'MSORG', 'slug' => 'msorg-dupe', 'adapter_key' => 'msorg_dupe', 'status' => 'active']);
    ProviderConnection::create(['provider_adapter_id' => $adapter->id, 'name' => 'Existing', 'slug' => 'existing', 'adapter' => 'msorg_dupe', 'website_url' => 'https://provider.test/home', 'status' => 'active']);

    $this->actingAs($admin, 'platform_admin')->postJson('/admin/provider-connections/catalogue', [
        'provider_adapter_id' => $adapter->id, 'name' => 'Duplicate', 'slug' => 'duplicate',
        'website_url' => 'https://provider.test/about', 'status' => 'active',
    ])->assertUnprocessable()->assertJsonValidationErrors('website_url');
});
