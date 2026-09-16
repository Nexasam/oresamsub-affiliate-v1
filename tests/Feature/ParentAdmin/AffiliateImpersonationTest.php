<?php

use App\Models\PlatformImpersonationToken;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates and consumes a tenant-bound handoff for any owned affiliate account', function (string $roleName) {
    [$parent, $admin, $levels] = managedParent('impersonation-'.strtolower($roleName));
    $affiliate = unattachedAffiliate('impersonation-affiliate-'.strtolower($roleName));
    $affiliate->update([
        'parent_business_id' => $parent->id,
        'parent_reseller_level_id' => $levels[0]->id,
        'activation_status' => 1,
        'domain_url' => 'affiliate-'.$roleName.'.test',
    ]);
    $role = Role::create(['role_name' => $roleName]);
    $user = User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'role_id' => $role->id,
        'username' => 'target-'.strtolower($roleName), 'first_name' => 'Target', 'last_name' => $roleName,
        'email' => strtolower($roleName).'@example.test', 'password' => 'password123', 'pin' => '4321',
    ]);

    $response = $this->actingAs($admin, 'parent_admin')
        ->post("/parent-admin/affiliates/{$affiliate->id}/users/{$user->id}/impersonate")
        ->assertRedirect();

    $url = $response->headers->get('Location');
    $token = basename(parse_url($url, PHP_URL_PATH));
    $record = PlatformImpersonationToken::sole();

    expect($record->parent_admin_id)->toBe($admin->id)
        ->and($record->affiliate_id)->toBe($affiliate->id)
        ->and($record->user_id)->toBe($user->id)
        ->and($record->return_url)->toBe(route('parent-admin.operations.index', ['affiliate_id' => $affiliate->id]));

    $this->withSession(['affiliate' => $affiliate])
        ->withServerVariables(['HTTP_HOST' => 'affiliate-'.$roleName.'.test'])
        ->get("/parent-impersonation/{$token}")
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user, 'web');
    expect(session('platform_impersonation.parent_admin_id'))->toBe($admin->id);

    $this->withSession(['affiliate' => $affiliate])
        ->withServerVariables(['HTTP_HOST' => 'affiliate-'.$roleName.'.test'])
        ->get("/parent-impersonation/{$token}")
        ->assertGone();

    $this->post('/platform-impersonation/exit')
        ->assertRedirect(route('parent-admin.operations.index', ['affiliate_id' => $affiliate->id]));
})->with(['Admin', 'User']);

it('rejects foreign affiliate accounts and nested parent impersonation', function () {
    [$parent, $admin] = managedParent('impersonation-owner');
    [$foreign, $foreignAdmin, $foreignLevels] = managedParent('impersonation-foreign');
    $foreignAffiliate = unattachedAffiliate('foreign-impersonation-affiliate');
    $foreignAffiliate->update([
        'parent_business_id' => $foreign->id, 'parent_reseller_level_id' => $foreignLevels[0]->id,
        'activation_status' => 1, 'domain_url' => 'foreign-affiliate.test',
    ]);
    $role = Role::create(['role_name' => 'User']);
    $foreignUser = User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $foreignAffiliate->id, 'role_id' => $role->id, 'username' => 'foreign-user',
        'first_name' => 'Foreign', 'last_name' => 'User', 'email' => 'foreign-user@example.test',
        'password' => 'password123', 'pin' => '4321',
    ]);

    $this->actingAs($admin, 'parent_admin')
        ->post("/parent-admin/affiliates/{$foreignAffiliate->id}/users/{$foreignUser->id}/impersonate")
        ->assertNotFound();

    $this->actingAs($foreignAdmin, 'parent_admin')
        ->withSession(['parent_impersonation' => ['platform_admin_id' => 1]])
        ->post("/parent-admin/affiliates/{$foreignAffiliate->id}/users/{$foreignUser->id}/impersonate")
        ->assertStatus(409);

    expect(PlatformImpersonationToken::count())->toBe(0);
});

it('rejects an expired parent impersonation handoff', function () {
    [$parent, $admin, $levels] = managedParent('expired-impersonation');
    $affiliate = unattachedAffiliate('expired-impersonation-affiliate');
    $affiliate->update([
        'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $levels[0]->id,
        'activation_status' => 1, 'domain_url' => 'expired-affiliate.test',
    ]);
    $role = Role::create(['role_name' => 'User']);
    $user = User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'role_id' => $role->id, 'username' => 'expired-user',
        'first_name' => 'Expired', 'last_name' => 'User', 'email' => 'expired-user@example.test',
        'password' => 'password123', 'pin' => '4321',
    ]);
    $plainToken = 'expired-parent-impersonation-token';
    PlatformImpersonationToken::create([
        'parent_admin_id' => $admin->id, 'affiliate_id' => $affiliate->id, 'user_id' => $user->id,
        'token_hash' => hash('sha256', $plainToken), 'return_url' => route('parent-admin.operations.index'),
        'expires_at' => now()->subSecond(),
    ]);

    $this->withSession(['affiliate' => $affiliate])
        ->withServerVariables(['HTTP_HOST' => 'expired-affiliate.test'])
        ->get("/parent-impersonation/{$plainToken}")
        ->assertGone();

    $this->assertGuest('web');
});
