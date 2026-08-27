<?php

use App\Models\Admin;
use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\ProviderConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function connectionReviewer(): Admin
{
    return Admin::create([
        'name' => 'Connection Reviewer', 'email' => 'connection-reviewer@example.test',
        'password' => 'secret-password', 'active' => true,
    ]);
}

function reviewableConnection(string $approvalStatus = 'pending'): ParentProviderConnection
{
    $parent = ParentBusiness::create(['name' => 'Review Parent', 'slug' => 'review-parent']);
    $adapter = ProviderConnection::create([
        'name' => 'Configurable HTTP', 'slug' => 'review-http', 'adapter' => 'review_http',
        'capabilities' => ['services' => ['data'], 'methods' => ['POST'], 'credential_fields' => ['api_public_key']],
        'status' => 'active',
    ]);

    return ParentProviderConnection::create([
        'parent_business_id' => $parent->id,
        'provider_connection_id' => $adapter->id,
        'name' => 'Parent primary',
        'base_url' => 'https://provider.example/api',
        'credentials' => ['api_public_key' => 'never-show-this-secret'],
        'settings' => [
            'http_method' => 'POST',
            'timeout_seconds' => 30,
            'endpoints' => ['data' => 'https://provider.example/api/data'],
            'request_parameters' => [['key' => 'phone', 'type' => 'runtime', 'value' => 'phone_number']],
            'request_headers' => [['key' => 'Authorization', 'type' => 'credential', 'value' => 'api_public_key']],
            'success_conditions' => [['key' => 'status', 'value' => 'success']],
        ],
        'status' => 'active',
        'approval_status' => $approvalStatus,
        'submitted_at' => now(),
    ]);
}

it('protects the parent provider review queue with the platform guard', function () {
    $connection = reviewableConnection();

    $this->get('/admin/provider-connections')->assertRedirect('/admin/login');
    $this->getJson('/admin/provider-connections/data')->assertUnauthorized();
    $this->patchJson("/admin/provider-connections/{$connection->id}/review", ['action' => 'approve'])->assertUnauthorized();
});

it('renders a redacted pending connection review queue', function () {
    $connection = reviewableConnection();
    $connection->update(['settings' => [
        ...$connection->settings,
        'request_headers' => [['key' => 'Authorization', 'type' => 'literal', 'value' => 'legacy-plain-secret']],
        'product_configs' => ['data' => [
            'request_headers' => [['key' => 'Authorization', 'type' => 'literal', 'value' => 'nested-legacy-secret']],
        ]],
    ]]);
    $admin = connectionReviewer();

    $this->actingAs($admin, 'platform_admin')->get('/admin/provider-connections')
        ->assertOk()->assertSee('Provider connection reviews')->assertSee('Pending approval')
        ->assertSee('dark:bg-slate-900', false)
        ->assertSee('dark:text-slate-100', false);

    $response = $this->actingAs($admin, 'platform_admin')->getJson('/admin/provider-connections/data')
        ->assertOk()
        ->assertJsonPath('connections.0.id', $connection->id)
        ->assertJsonPath('connections.0.parent_business.name', 'Review Parent')
        ->assertJsonPath('connections.0.credential_status.api_public_key', true)
        ->assertJsonMissing(['never-show-this-secret'])
        ->assertJsonMissing(['legacy-plain-secret'])
        ->assertJsonMissing(['nested-legacy-secret']);

    expect(json_encode($response->json()))->not->toContain('never-show-this-secret')
        ->and(json_encode($response->json()))->not->toContain('legacy-plain-secret')
        ->and(json_encode($response->json()))->not->toContain('nested-legacy-secret')
        ->and(DB::table('parent_provider_connections')->value('credentials'))->not->toContain('never-show-this-secret');
});

it('allows only a platform admin to approve a pending connection', function () {
    $connection = reviewableConnection();
    $admin = connectionReviewer();
    $connection->update(['settings' => [...$connection->settings, 'is_primary' => true]]);
    $existingPrimary = $connection->parentBusiness->providerConnections()->create([
        'provider_connection_id' => $connection->provider_connection_id,
        'name' => 'Existing approved primary',
        'settings' => ['is_primary' => true],
        'status' => 'active',
        'approval_status' => 'approved',
    ]);

    $this->actingAs($admin, 'platform_admin')
        ->patchJson("/admin/provider-connections/{$connection->id}/review", ['action' => 'approve'])
        ->assertOk()->assertJsonPath('connection.approval_status', 'approved');

    $connection->refresh();
    expect($connection->approved_by_admin_id)->toBe($admin->id)
        ->and($connection->approved_at)->not->toBeNull()
        ->and($connection->rejection_reason)->toBeNull()
        ->and($existingPrimary->fresh()->settings['is_primary'])->toBeFalse();
});

it('requires and records a rejection reason', function () {
    $connection = reviewableConnection();
    $admin = connectionReviewer();

    $this->actingAs($admin, 'platform_admin')
        ->patchJson("/admin/provider-connections/{$connection->id}/review", ['action' => 'reject'])
        ->assertUnprocessable()->assertJsonValidationErrors('reason');

    $this->actingAs($admin, 'platform_admin')
        ->patchJson("/admin/provider-connections/{$connection->id}/review", [
            'action' => 'reject', 'reason' => 'The submitted endpoint could not be verified.',
        ])->assertOk()->assertJsonPath('connection.approval_status', 'rejected');

    expect($connection->fresh()->rejection_reason)->toBe('The submitted endpoint could not be verified.');
});

it('does not review a connection that is no longer pending', function () {
    $connection = reviewableConnection('approved');

    $this->actingAs(connectionReviewer(), 'platform_admin')
        ->patchJson("/admin/provider-connections/{$connection->id}/review", ['action' => 'approve'])
        ->assertUnprocessable()->assertJsonValidationErrors('action');
});
