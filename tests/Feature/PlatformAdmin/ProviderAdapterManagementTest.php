<?php

use App\Models\Admin;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\Product;
use App\Models\ProviderConnection;
use App\Models\ProviderAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function platformAdapterAdmin(): Admin
{
    return Admin::create([
        'name' => 'Platform Owner',
        'email' => 'adapters@example.test',
        'password' => 'secret-password',
        'active' => true,
    ]);
}

function platformAdapterPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Configurable HTTP',
        'slug' => ' Configurable HTTP ',
        'adapter' => ' Configurable HTTP ',
        'status' => 'active',
        'capabilities' => [
            'services' => ['data', 'airtime'],
            'methods' => ['POST', 'GET'],
            'credential_fields' => ['api_public_key', 'api_secret_key'],
        ],
    ], $overrides);
}

it('protects the provider adapter catalogue with the platform admin guard', function () {
    $this->get('/admin/provider-adapters')->assertRedirect('/admin/login');
    $this->getJson('/admin/provider-adapters/data')->assertUnauthorized();
    $this->postJson('/admin/provider-adapters', platformAdapterPayload())->assertUnauthorized();
});

it('renders the provider adapter catalogue and lists existing definitions', function () {
    $adapter = ProviderAdapter::create([
        'name' => 'Existing adapter', 'slug' => 'existing-adapter', 'adapter_key' => 'existing_adapter',
        'capabilities' => ['services' => ['data'], 'methods' => ['POST'], 'credential_fields' => []],
        'status' => 'active',
    ]);

    $admin = platformAdapterAdmin();

    $this->actingAs($admin, 'platform_admin')
        ->get('/admin/provider-adapters')
        ->assertOk()
        ->assertSee('Provider adapters')
        ->assertSee('Approved adapter catalogue');

    $this->actingAs($admin, 'platform_admin')
        ->getJson('/admin/provider-adapters/data')
        ->assertOk()
        ->assertJsonPath('adapters.0.id', $adapter->id)
        ->assertJsonPath('allowed.services.0.slug', 'data');
});

it('uses every global product as an available adapter service', function () {
    Product::create([
        'api_id' => 'epin-service', 'product_name' => 'E-Pins', 'slug' => 'e_pins',
        'visibility' => 1, 'active_status' => 1,
    ]);
    $admin = platformAdapterAdmin();

    $this->actingAs($admin, 'platform_admin')->getJson('/admin/provider-adapters/data')
        ->assertOk()->assertJsonPath('allowed.services.0.slug', 'e_pins');

    $payload = platformAdapterPayload();
    $payload['capabilities'] = [
        'services' => ['e_pins'], 'methods' => ['POST'], 'credential_fields' => ['api_public_key'],
    ];

    $this->actingAs($admin, 'platform_admin')->postJson('/admin/provider-adapters', $payload)
        ->assertCreated()->assertJsonPath('adapter.capabilities.services.0', 'e_pins');
});

it('creates a normalized approved adapter definition', function () {
    $this->actingAs(platformAdapterAdmin(), 'platform_admin')
        ->postJson('/admin/provider-adapters', platformAdapterPayload())
        ->assertCreated()
        ->assertJsonPath('adapter.slug', 'configurable-http')
        ->assertJsonPath('adapter.adapter', 'configurable_http');

    $adapter = ProviderAdapter::sole();
    expect($adapter->capabilities)->toBe([
        'services' => ['data', 'airtime'],
        'methods' => ['POST', 'GET'],
        'credential_fields' => ['api_public_key', 'api_secret_key'],
    ]);
});

it('updates and deactivates adapters without exposing a delete route', function () {
    $adapter = ProviderAdapter::create([
        'name' => 'HTTP adapter', 'slug' => 'http-adapter', 'adapter_key' => 'http_adapter',
        'capabilities' => ['services' => ['data'], 'methods' => ['POST'], 'credential_fields' => []],
        'status' => 'active',
    ]);

    $admin = platformAdapterAdmin();

    $this->actingAs($admin, 'platform_admin')
        ->putJson("/admin/provider-adapters/{$adapter->id}", platformAdapterPayload([
            'name' => 'HTTP adapter v2', 'slug' => 'http-adapter', 'adapter' => 'http_adapter', 'status' => 'inactive',
        ]))
        ->assertOk()
        ->assertJsonPath('adapter.status', 'inactive');

    expect($adapter->fresh()->name)->toBe('HTTP adapter v2');
    $this->actingAs($admin, 'platform_admin')
        ->deleteJson("/admin/provider-adapters/{$adapter->id}")
        ->assertMethodNotAllowed();
});

it('rejects duplicate machine keys and unsupported capabilities', function () {
    ProviderAdapter::create([
        'name' => 'Existing', 'slug' => 'existing', 'adapter_key' => 'existing_adapter',
        'capabilities' => [], 'status' => 'active',
    ]);
    $admin = platformAdapterAdmin();

    $this->actingAs($admin, 'platform_admin')->postJson('/admin/provider-adapters', platformAdapterPayload([
        'slug' => 'existing', 'adapter' => 'existing_adapter',
        'capabilities' => [
            'services' => ['crypto'], 'methods' => ['DELETE'], 'credential_fields' => ['plain_token'],
        ],
    ]))->assertUnprocessable()->assertJsonValidationErrors([
        'slug', 'adapter_key', 'capabilities.services.0', 'capabilities.methods.0', 'capabilities.credential_fields.0',
    ]);
});

it('shows only active adapters to parents while preserving saved inactive connections', function () {
    $active = ProviderAdapter::create([
        'name' => 'Active adapter', 'slug' => 'active-adapter', 'adapter_key' => 'active_adapter',
        'capabilities' => [], 'status' => 'active',
    ]);
    $inactive = ProviderAdapter::create([
        'name' => 'Inactive adapter', 'slug' => 'inactive-adapter', 'adapter_key' => 'inactive_adapter',
        'capabilities' => [], 'status' => 'inactive',
    ]);
    $parent = ParentBusiness::create(['name' => 'Parent', 'slug' => 'adapter-parent']);
    $parentAdmin = ParentAdmin::create([
        'parent_business_id' => $parent->id, 'name' => 'Parent Owner', 'email' => 'parent-adapters@example.test',
        'password' => 'secret-password', 'active' => true,
    ]);
    $provider = ProviderConnection::create(['provider_adapter_id' => $inactive->id, 'name' => 'Inactive provider', 'slug' => 'inactive-provider', 'adapter' => 'inactive_adapter', 'status' => 'inactive']);
    $saved = ParentProviderConnection::create([
        'parent_business_id' => $parent->id, 'provider_adapter_id' => $inactive->id, 'provider_connection_id' => $provider->id, 'name' => 'Legacy inactive',
    ]);

    $response = $this->actingAs($parentAdmin, 'parent_admin')->getJson('/parent-admin/provider-connections/data')->assertOk();

    expect(collect($response->json('adapters'))->pluck('id')->all())->toBe([$active->id])
        ->and($response->json('connections.0.id'))->toBe($saved->id)
        ->and($response->json('connections.0.provider_connection.status'))->toBe('inactive');
});
