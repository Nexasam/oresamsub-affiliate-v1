<?php

use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\ProviderConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function providerWorkspace(string $slug = 'provider-parent'): array
{
    $parent = ParentBusiness::create(['name' => ucfirst($slug), 'slug' => $slug]);
    $admin = ParentAdmin::create([
        'parent_business_id' => $parent->id, 'name' => 'Provider Owner',
        'email' => "{$slug}@example.test", 'password' => 'secret-password', 'active' => true,
    ]);
    $adapter = ProviderConnection::create([
        'name' => 'Configurable HTTP', 'slug' => "http-{$slug}", 'adapter' => 'configurable_http_'.str_replace('-', '_', $slug),
        'capabilities' => ['services' => ['data', 'airtime', 'cable', 'electricity']], 'status' => 'active',
    ]);

    return [$parent, $admin, $adapter];
}

function providerPayload(int $adapterId, array $overrides = []): array
{
    return array_replace_recursive([
        'provider_connection_id' => $adapterId,
        'name' => 'Affatech Primary',
        'base_url' => 'https://affatech.example/api',
        'status' => 'active',
        'is_primary' => true,
        'credentials' => ['api_public_key' => 'public-secret', 'api_secret_key' => 'private-secret', 'api_password' => null],
        'settings' => [
            'http_method' => 'POST', 'timeout_seconds' => 30,
            'endpoints' => ['data' => 'https://affatech.example/api/data', 'airtime' => 'https://affatech.example/api/airtime', 'cable' => null, 'electricity' => null],
            'request_parameters' => [['key' => 'mobile_number', 'type' => 'runtime', 'value' => 'phone_number']],
            'request_headers' => [['key' => 'Authorization', 'type' => 'credential', 'value' => 'api_public_key']],
            'network_mapping' => ['MTN' => '1'],
            'success_conditions' => [['key' => 'status', 'value' => 'success']],
            'success_message_path' => 'data.message', 'failure_message_path' => 'error.message',
        ],
    ], $overrides);
}

it('renders the parent provider connection workspace', function () {
    [, $admin] = providerWorkspace();

    $this->actingAs($admin, 'parent_admin')->get('/parent-admin/provider-connections')
        ->assertOk()->assertSee('Provider connections')->assertSee('Request mapping')->assertSee('Success conditions');
});

it('creates a parent scoped connection with encrypted masked credentials', function () {
    [$parent, $admin, $adapter] = providerWorkspace('create-provider');

    $response = $this->actingAs($admin, 'parent_admin')->postJson('/parent-admin/provider-connections', providerPayload($adapter->id))
        ->assertCreated()->assertJsonMissing(['public-secret'])->assertJsonMissing(['private-secret']);

    $connection = ParentProviderConnection::sole();
    expect($connection->parent_business_id)->toBe($parent->id)
        ->and($connection->credentials['api_public_key'])->toBe('public-secret')
        ->and(DB::table('parent_provider_connections')->value('credentials'))->not->toContain('private-secret')
        ->and($response->json('connection.credential_status.api_public_key'))->toBeTrue();
});

it('preserves masked credentials and enforces one primary connection per parent', function () {
    [$parent, $admin, $adapter] = providerWorkspace('primary-provider');
    $first = $parent->providerConnections()->create([
        'provider_connection_id' => $adapter->id, 'name' => 'First', 'credentials' => ['api_public_key' => 'keep-me'],
        'settings' => ['is_primary' => true],
    ]);
    $second = $parent->providerConnections()->create([
        'provider_connection_id' => $adapter->id, 'name' => 'Second', 'credentials' => ['api_public_key' => 'preserve-me'],
        'settings' => ['is_primary' => false],
    ]);

    $this->actingAs($admin, 'parent_admin')->putJson("/parent-admin/provider-connections/{$second->id}", providerPayload($adapter->id, [
        'name' => 'Second', 'credentials' => ['api_public_key' => null, 'api_secret_key' => null, 'api_password' => null],
    ]))->assertOk();

    expect($second->fresh()->credentials['api_public_key'])->toBe('preserve-me')
        ->and($first->fresh()->settings['is_primary'])->toBeFalse()
        ->and($second->fresh()->settings['is_primary'])->toBeTrue();
});

it('rejects cross parent connection access and unsafe mappings', function () {
    [, $admin, $adapter] = providerWorkspace('owned-provider');
    [$foreignParent] = providerWorkspace('foreign-provider');
    $foreign = $foreignParent->providerConnections()->create(['provider_connection_id' => $adapter->id, 'name' => 'Foreign']);

    $this->actingAs($admin, 'parent_admin')->putJson("/parent-admin/provider-connections/{$foreign->id}", providerPayload($adapter->id))->assertNotFound();
    $this->actingAs($admin, 'parent_admin')->postJson('/parent-admin/provider-connections', providerPayload($adapter->id, [
        'settings' => ['request_parameters' => [['key' => 'phone', 'type' => 'runtime', 'value' => 'unknown_runtime']]],
    ]))->assertUnprocessable();
});

it('enforces the selected adapter capabilities on a new parent connection', function () {
    [, $admin, $adapter] = providerWorkspace('capability-provider');
    $adapter->update(['capabilities' => [
        'services' => ['data'],
        'methods' => ['POST'],
        'credential_fields' => ['api_public_key'],
    ]]);

    $this->actingAs($admin, 'parent_admin')->postJson('/parent-admin/provider-connections', providerPayload($adapter->id, [
        'credentials' => ['api_secret_key' => 'not-allowed'],
        'settings' => [
            'http_method' => 'GET',
            'endpoints' => ['airtime' => 'https://affatech.example/api/airtime'],
            'request_headers' => [['key' => 'Authorization', 'type' => 'credential', 'value' => 'api_secret_key']],
        ],
    ]))->assertUnprocessable()->assertJsonValidationErrors([
        'settings.http_method', 'settings.endpoints.airtime', 'credentials.api_secret_key', 'settings.request_headers',
    ]);
});

it('allows an existing connection to retain its deactivated adapter while being edited', function () {
    [$parent, $admin, $adapter] = providerWorkspace('inactive-edit-provider');
    $connection = $parent->providerConnections()->create([
        'provider_connection_id' => $adapter->id,
        'name' => 'Legacy connection',
        'settings' => ['is_primary' => false],
    ]);
    $adapter->update(['status' => 'inactive']);

    $this->actingAs($admin, 'parent_admin')->putJson(
        "/parent-admin/provider-connections/{$connection->id}",
        providerPayload($adapter->id, ['name' => 'Updated legacy connection'])
    )->assertOk();

    expect($connection->fresh()->name)->toBe('Updated legacy connection');
});
