<?php

use App\Models\Admin;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\Product;
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
            'product_configs' => [
                'data' => [
                    'request_parameters' => [['key' => 'data_phone', 'type' => 'runtime', 'value' => 'phone_number']],
                    'request_headers' => [['key' => 'Authorization', 'type' => 'credential', 'value' => 'api_public_key']],
                    'network_mapping' => ['MTN' => 'DATA-1'],
                    'success_conditions' => [['key' => 'data.status', 'value' => 'success']],
                    'success_message_path' => 'data.message', 'failure_message_path' => 'data.error',
                ],
                'airtime' => [
                    'request_parameters' => [['key' => 'airtime_phone', 'type' => 'runtime', 'value' => 'phone_number']],
                    'request_headers' => [],
                    'network_mapping' => ['MTN' => 'AIRTIME-1'],
                    'success_conditions' => [['key' => 'airtime.status', 'value' => 'success']],
                    'success_message_path' => 'airtime.message', 'failure_message_path' => 'airtime.error',
                ],
            ],
        ],
    ], $overrides);
}

it('renders the parent provider connection workspace', function () {
    [, $admin] = providerWorkspace();

    $this->actingAs($admin, 'parent_admin')->get('/parent-admin/provider-connections?create=1')
        ->assertOk()->assertSee('Provider connections')->assertSee('Product request configuration')
        ->assertSee('Each product has its own payload, headers, network IDs and response rules.')
        ->assertDontSee('structuredClone(this.form)', false)
        ->assertDontSee('axios({method,url,data:this.payload()})', false);
});

it('uses a blade form for parent connection submission and redirects with feedback', function () {
    [, $admin, $adapter] = providerWorkspace('blade-provider');

    $this->actingAs($admin, 'parent_admin')->get('/parent-admin/provider-connections?create=1')
        ->assertOk()
        ->assertSee('<form method="POST"', false)
        ->assertDontSee('@submit.prevent="save"', false)
        ->assertDontSee('axios({method,url,data:this.payload()})', false);

    $this->actingAs($admin, 'parent_admin')
        ->post('/parent-admin/provider-connections', providerPayload($adapter->id))
        ->assertRedirect(route('parent-admin.provider-connections.index'))
        ->assertSessionHas('success', 'Provider connection created and submitted for platform approval.');
});

it('server renders an owned connection for editing through a normal put form', function () {
    [$parent, $admin, $adapter] = providerWorkspace('blade-edit-provider');
    $this->actingAs($admin, 'parent_admin')
        ->postJson('/parent-admin/provider-connections', providerPayload($adapter->id))
        ->assertCreated();
    $connection = $parent->providerConnections()->sole();

    $this->actingAs($admin, 'parent_admin')
        ->get("/parent-admin/provider-connections?edit={$connection->id}")
        ->assertOk()
        ->assertSee('Affatech Primary')
        ->assertSee('name="_method" value="PUT"', false)
        ->assertSee(route('parent-admin.provider-connections.update', $connection), false);
});

it('creates a parent scoped connection with encrypted masked credentials', function () {
    [$parent, $admin, $adapter] = providerWorkspace('create-provider');

    $response = $this->actingAs($admin, 'parent_admin')->postJson('/parent-admin/provider-connections', providerPayload($adapter->id))
        ->assertCreated()->assertJsonMissing(['public-secret'])->assertJsonMissing(['private-secret']);

    $connection = ParentProviderConnection::sole();
    expect($connection->parent_business_id)->toBe($parent->id)
        ->and($connection->approval_status)->toBe('pending')
        ->and($connection->submitted_at)->not->toBeNull()
        ->and($connection->credentials['api_public_key'])->toBe('public-secret')
        ->and(DB::table('parent_provider_connections')->value('credentials'))->not->toContain('private-secret')
        ->and($response->json('connection.credential_status.api_public_key'))->toBeTrue();
});

it('keeps approval for non-sensitive connection changes', function () {
    [$parent, $admin, $adapter] = providerWorkspace('approved-display-edit');
    $this->actingAs($admin, 'parent_admin')->postJson('/parent-admin/provider-connections', providerPayload($adapter->id))->assertCreated();
    $connection = $parent->providerConnections()->sole();
    $connection->update(['approval_status' => 'approved', 'approved_at' => now(), 'rejection_reason' => null]);

    $this->actingAs($admin, 'parent_admin')->putJson("/parent-admin/provider-connections/{$connection->id}", providerPayload($adapter->id, [
        'name' => 'Renamed connection',
        'status' => 'inactive',
        'is_primary' => false,
        'credentials' => ['api_public_key' => null, 'api_secret_key' => null, 'api_password' => null],
    ]))->assertOk()->assertJsonPath('connection.approval_status', 'approved');
});

it('returns sensitive connection changes to pending approval', function () {
    [$parent, $admin, $adapter] = providerWorkspace('approved-sensitive-edit');
    $this->actingAs($admin, 'parent_admin')->postJson('/parent-admin/provider-connections', providerPayload($adapter->id))->assertCreated();
    $connection = $parent->providerConnections()->sole();
    $connection->update([
        'approval_status' => 'approved', 'approved_at' => now(),
        'approved_by_admin_id' => Admin::create([
            'name' => 'Reviewer', 'email' => 'reviewer@example.test', 'password' => 'secret-password', 'active' => true,
        ])->id,
        'rejection_reason' => null,
    ]);

    $this->actingAs($admin, 'parent_admin')->putJson("/parent-admin/provider-connections/{$connection->id}", providerPayload($adapter->id, [
        'base_url' => 'https://changed-provider.example/api',
        'credentials' => ['api_public_key' => null, 'api_secret_key' => null, 'api_password' => null],
    ]))->assertOk()->assertJsonPath('connection.approval_status', 'pending');

    $connection->refresh();
    expect($connection->approved_at)->toBeNull()
        ->and($connection->approved_by_admin_id)->toBeNull()
        ->and($connection->rejection_reason)->toBeNull();
});

it('resubmits a rejected connection as pending when the parent corrects it', function () {
    [$parent, $admin, $adapter] = providerWorkspace('rejected-resubmit');
    $this->actingAs($admin, 'parent_admin')->postJson('/parent-admin/provider-connections', providerPayload($adapter->id))->assertCreated();
    $connection = $parent->providerConnections()->sole();
    $connection->update(['approval_status' => 'rejected', 'rejection_reason' => 'Endpoint ownership could not be confirmed.']);

    $this->actingAs($admin, 'parent_admin')->putJson("/parent-admin/provider-connections/{$connection->id}", providerPayload($adapter->id, [
        'base_url' => 'https://corrected-provider.example/api',
        'credentials' => ['api_public_key' => null, 'api_secret_key' => null, 'api_password' => null],
    ]))->assertOk()->assertJsonPath('connection.approval_status', 'pending');

    expect($connection->fresh()->rejection_reason)->toBeNull();
});

it('preserves masked credentials without displacing an approved primary before reapproval', function () {
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
        ->and($first->fresh()->settings['is_primary'])->toBeTrue()
        ->and($second->fresh()->settings['is_primary'])->toBeTrue()
        ->and($second->fresh()->approval_status)->toBe('pending');
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

it('configures endpoints for every supported global product', function () {
    Product::create([
        'api_id' => 'result-service', 'product_name' => 'Result checker', 'slug' => 'result_checker',
        'visibility' => 1, 'active_status' => 1,
    ]);
    [, $admin, $adapter] = providerWorkspace('all-product-provider');
    $adapter->update(['capabilities' => [
        'services' => ['result_checker'], 'methods' => ['POST'], 'credential_fields' => ['api_public_key'],
    ]]);

    $payload = providerPayload($adapter->id, [
        'settings' => ['endpoints' => [
            'data' => null, 'airtime' => null, 'cable' => null, 'electricity' => null,
            'result_checker' => 'https://provider.example/api/result-checker',
        ]],
        'credentials' => ['api_public_key' => 'allowed', 'api_secret_key' => null, 'api_password' => null],
    ]);
    $payload['settings']['product_configs'] = [
        'result_checker' => [
            'request_parameters' => [['key' => 'exam', 'type' => 'runtime', 'value' => 'exam_type']],
            'request_headers' => [],
            'network_mapping' => [],
            'success_conditions' => [['key' => 'status', 'value' => 'success']],
            'success_message_path' => 'data.message',
            'failure_message_path' => 'error.message',
        ],
    ];

    $this->actingAs($admin, 'parent_admin')->postJson('/parent-admin/provider-connections', $payload)->assertCreated();

    $this->actingAs($admin, 'parent_admin')->getJson('/parent-admin/provider-connections/data')
        ->assertOk()->assertJsonPath('products.0.slug', 'result_checker');
});

it('stores independent mapping and response configuration for every product', function () {
    [$parent, $admin, $adapter] = providerWorkspace('per-product-configuration');

    $this->actingAs($admin, 'parent_admin')->postJson(
        '/parent-admin/provider-connections',
        providerPayload($adapter->id)
    )->assertCreated();

    $configs = $parent->providerConnections()->sole()->settings['product_configs'];
    expect($configs['data']['request_parameters'][0]['key'])->toBe('data_phone')
        ->and($configs['airtime']['request_parameters'][0]['key'])->toBe('airtime_phone')
        ->and($configs['data']['network_mapping']['MTN'])->toBe('DATA-1')
        ->and($configs['airtime']['network_mapping']['MTN'])->toBe('AIRTIME-1')
        ->and($configs['data']['success_conditions'][0]['key'])->toBe('data.status')
        ->and($configs['airtime']['success_conditions'][0]['key'])->toBe('airtime.status');
});

it('rejects an unsafe mapping inside an individual product configuration', function () {
    [, $admin, $adapter] = providerWorkspace('unsafe-product-configuration');

    $this->actingAs($admin, 'parent_admin')->postJson('/parent-admin/provider-connections', providerPayload($adapter->id, [
        'settings' => ['product_configs' => ['data' => [
            'request_parameters' => [['key' => 'phone', 'type' => 'runtime', 'value' => 'unknown_runtime']],
        ]]],
    ]))->assertUnprocessable()->assertJsonValidationErrors('settings.product_configs.data.request_parameters');
});
