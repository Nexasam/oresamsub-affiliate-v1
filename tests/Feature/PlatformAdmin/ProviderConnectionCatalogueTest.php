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

it('lets a platform admin edit an existing catalogue connection without replacing it', function () {
    $admin = Admin::create(['name' => 'Platform', 'email' => 'catalogue-edit@example.test', 'password' => 'password', 'active' => true]);
    $adapter = ProviderAdapter::create([
        'name' => 'Configurable HTTP Provider', 'slug' => 'configurable-edit', 'adapter_key' => 'configurable_http',
        'capabilities' => ['services' => ['data'], 'credential_fields' => ['api_token']],
        'settings' => ['http_method' => 'POST', 'timeout_seconds' => 30], 'version' => 1, 'status' => 'active',
    ]);
    $connection = ProviderConnection::create([
        'provider_adapter_id' => $adapter->id, 'name' => 'BILINK', 'slug' => 'bilink-edit',
        'adapter' => 'configurable_http', 'website_url' => 'https://bilink.test',
        'settings' => ['http_method' => 'POST', 'endpoints' => ['data' => '/old']], 'status' => 'active',
    ]);

    $this->actingAs($admin, 'platform_admin')
        ->get('/admin/provider-connections/catalogue')
        ->assertOk()
        ->assertSee('Edit BILINK');

    $this->actingAs($admin, 'platform_admin')->putJson('/admin/provider-connections/catalogue/'.$connection->id, [
        'provider_adapter_id' => $adapter->id,
        'name' => 'BILINK Nigeria',
        'slug' => 'bilink-ng',
        'website_url' => 'https://bilink.test',
        'base_url' => 'https://bilink.test/api',
        'documentation_url' => 'https://bilink.test/docs',
        'settings_overrides' => ['endpoints' => ['data' => '/autobiz_vending_index.php']],
        'status' => 'inactive',
    ])->assertOk()->assertJsonPath('connection.id', $connection->id);

    expect(ProviderConnection::count())->toBe(1)
        ->and($connection->fresh()->name)->toBe('BILINK Nigeria')
        ->and($connection->fresh()->slug)->toBe('bilink-ng')
        ->and($connection->fresh()->settings['endpoints']['data'])->toBe('/autobiz_vending_index.php')
        ->and($connection->fresh()->status)->toBe('inactive');
});
