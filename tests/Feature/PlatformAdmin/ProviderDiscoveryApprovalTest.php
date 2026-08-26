<?php

use App\Models\Admin;
use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\ProviderAdapter;
use App\Models\ProviderConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a reusable shared connection when approving an adapter discovery request', function () {
    $reviewer = Admin::create(['name' => 'Reviewer', 'email' => 'discovery-review@example.test', 'password' => 'password', 'active' => true]);
    $adapter = ProviderAdapter::create([
        'name' => 'MSORG', 'slug' => 'msorg-review', 'adapter_key' => 'msorg_review',
        'capabilities' => ['services' => ['data'], 'credential_fields' => ['api_public_key']],
        'settings' => ['http_method' => 'POST', 'product_configs' => ['data' => ['success_message_path' => 'message']]],
        'version' => 2, 'status' => 'active',
    ]);
    $parent = ParentBusiness::create(['name' => 'Requesting Parent', 'slug' => 'requesting-parent']);
    $pending = ParentProviderConnection::create([
        'parent_business_id' => $parent->id, 'provider_adapter_id' => $adapter->id,
        'provider_connection_id' => null, 'request_type' => 'discovery', 'name' => 'PaulTechs Primary',
        'proposed_provider_name' => 'PaulTechs', 'proposed_base_url' => 'https://paultechs.test/api',
        'proposed_documentation_url' => 'https://paultechs.test/docs', 'credentials' => ['api_public_key' => 'never-show-me'],
        'settings' => ['is_primary' => true], 'status' => 'active', 'approval_status' => 'pending',
    ]);

    $this->actingAs($reviewer, 'platform_admin')->patchJson("/admin/provider-connections/{$pending->id}/review", ['action' => 'approve'])
        ->assertOk()->assertJsonPath('connection.approval_status', 'approved')
        ->assertJsonPath('connection.request_type', 'discovery')
        ->assertJsonPath('connection.credential_status.api_public_key', true)
        ->assertJsonMissing(['never-show-me']);

    $shared = ProviderConnection::where('name', 'PaulTechs')->sole();
    expect($shared->provider_adapter_id)->toBe($adapter->id)
        ->and($shared->settings)->toBe($adapter->settings)
        ->and($shared->adapter_version)->toBe(2)
        ->and($pending->fresh()->provider_connection_id)->toBe($shared->id)
        ->and($pending->fresh()->credentials['api_public_key'])->toBe('never-show-me');
});

it('reuses an approved connection for the same adapter and normalized provider host', function () {
    $reviewer = Admin::create(['name' => 'Reviewer', 'email' => 'reuse-review@example.test', 'password' => 'password', 'active' => true]);
    $adapter = ProviderAdapter::create(['name' => 'MSORG', 'slug' => 'msorg-reuse', 'adapter_key' => 'msorg_reuse', 'status' => 'active']);
    $shared = ProviderConnection::create(['provider_adapter_id' => $adapter->id, 'name' => 'Known Site', 'slug' => 'known-site', 'adapter' => 'msorg_reuse', 'website_url' => 'https://www.known.test', 'status' => 'active']);
    $parent = ParentBusiness::create(['name' => 'Reuse Parent', 'slug' => 'reuse-parent']);
    $pending = ParentProviderConnection::create(['parent_business_id' => $parent->id, 'provider_adapter_id' => $adapter->id, 'request_type' => 'discovery', 'name' => 'Known', 'proposed_provider_name' => 'Known Duplicate', 'proposed_base_url' => 'https://known.test/api/v1', 'status' => 'active', 'approval_status' => 'pending']);

    $this->actingAs($reviewer, 'platform_admin')->patchJson("/admin/provider-connections/{$pending->id}/review", ['action' => 'approve'])->assertOk();

    expect(ProviderConnection::count())->toBe(1)
        ->and($pending->fresh()->provider_connection_id)->toBe($shared->id);
});
