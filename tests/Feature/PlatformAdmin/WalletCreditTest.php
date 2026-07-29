<?php

use App\Models\Admin;
use App\Models\Affiliate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('credits the selected affiliate user and writes an audit log', function () {
    $admin = Admin::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'password123', 'active' => true]);
    $affiliate = Affiliate::create([
        'name' => 'Tenant', 'slug' => 'tenant', 'affiliate_plan_id' => 1, 'ip_address' => '127.0.0.8',
        'contact_phone' => '08000000008', 'contact_email' => 'tenant@example.com',
        'parent_key' => 'tenant-key', 'parent_email' => 'parent@example.com',
    ]);
    $role = Role::create(['role_name' => 'User']);
    $user = User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'username' => 'customer', 'first_name' => 'Test',
        'last_name' => 'Customer', 'email' => 'customer@example.com', 'password' => 'password123',
        'role_id' => $role->id, 'main_wallet' => 100,
    ]);

    $this->actingAs($admin, 'platform_admin')
        ->postJson("/admin/affiliates/{$affiliate->id}/users/{$user->id}/credit", [
            'amount' => 250.50,
            'reason' => 'Verified manual bank transfer',
        ])
        ->assertOk()
        ->assertJsonPath('user.main_wallet', '350.5');

    expect((float) $user->fresh()->main_wallet)->toBe(350.5);
    $this->assertDatabaseHas('wallet_logs', [
        'affiliate_id' => $affiliate->id,
        'user_id' => $user->id,
        'action_by' => 'platform_admin:'.$admin->id,
        'transaction_category' => 'PLATFORM_ADMIN_WALLET_CREDITING',
    ]);
});

it('cannot credit a user through another affiliate url', function () {
    $admin = Admin::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'password123', 'active' => true]);
    $first = Affiliate::create(['name' => 'One', 'slug' => 'one', 'affiliate_plan_id' => 1, 'ip_address' => '127.0.0.1', 'contact_phone' => '0801', 'contact_email' => 'one@example.com', 'parent_key' => 'one', 'parent_email' => 'one-parent@example.com']);
    $second = Affiliate::create(['name' => 'Two', 'slug' => 'two', 'affiliate_plan_id' => 1, 'ip_address' => '127.0.0.2', 'contact_phone' => '0802', 'contact_email' => 'two@example.com', 'parent_key' => 'two', 'parent_email' => 'two-parent@example.com']);
    $role = Role::create(['role_name' => 'User']);
    $user = User::withoutGlobalScope('affiliate')->create(['affiliate_id' => $first->id, 'username' => 'customer', 'first_name' => 'Test', 'last_name' => 'Customer', 'email' => 'customer@example.com', 'password' => 'password123', 'role_id' => $role->id, 'main_wallet' => 100]);

    $this->actingAs($admin, 'platform_admin')
        ->postJson("/admin/affiliates/{$second->id}/users/{$user->id}/credit", ['amount' => 50, 'reason' => 'Invalid tenant credit'])
        ->assertNotFound();

    expect((float) $user->fresh()->main_wallet)->toBe(100.0);
});
