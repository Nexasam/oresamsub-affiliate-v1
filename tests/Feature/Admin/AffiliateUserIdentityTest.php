<?php

use App\Models\Affiliate;
use App\Models\AffiliateUserPlan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an admin to reuse customer identity values owned by another affiliate', function () {
    $adminRole = Role::create(['role_name' => 'Admin']);
    $userRole = Role::create(['role_name' => 'User']);
    $firstAffiliate = Affiliate::create([
        'name' => 'First Affiliate', 'slug' => 'first-affiliate', 'affiliate_plan_id' => 1,
        'ip_address' => '127.0.0.1', 'contact_phone' => '08010000001',
        'contact_email' => 'first-affiliate@example.com', 'parent_key' => 'first-key',
        'parent_email' => 'first-parent@example.com', 'domain_url' => 'first.example.test',
    ]);
    $secondAffiliate = Affiliate::create([
        'name' => 'Second Affiliate', 'slug' => 'second-affiliate', 'affiliate_plan_id' => 1,
        'ip_address' => '127.0.0.2', 'contact_phone' => '08010000002',
        'contact_email' => 'second-affiliate@example.com', 'parent_key' => 'second-key',
        'parent_email' => 'second-parent@example.com', 'domain_url' => 'second.example.test',
    ]);
    $plan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $firstAffiliate->id,
        'user_plan_name' => 'Basic',
        'plan_level' => 1,
    ]);
    $admin = User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $firstAffiliate->id, 'username' => 'first-admin',
        'first_name' => 'First', 'last_name' => 'Admin', 'phone_number' => '08020000001',
        'email' => 'first-admin@example.com', 'role_id' => $adminRole->id,
        'password' => 'StrongPass1!', 'email_verified_at' => now(),
    ]);
    User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $secondAffiliate->id, 'username' => 'shared-customer',
        'first_name' => 'Other', 'last_name' => 'Customer', 'phone_number' => '08030000001',
        'email' => 'shared@example.com', 'role_id' => $userRole->id,
        'password' => 'StrongPass1!',
    ]);

    $this->withSession(['affiliate' => $firstAffiliate])
        ->actingAs($admin)
        ->post('/admin/users/store', [
            'username' => 'shared-customer',
            'pin' => '1234',
            'first_name' => 'New',
            'last_name' => 'Customer',
            'phone_number' => '08030000001',
            'email' => 'shared@example.com',
            'user_plan_id' => $plan->id,
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.users.index'));

    expect(User::withoutGlobalScope('affiliate')
        ->where('affiliate_id', $firstAffiliate->id)
        ->where('email', 'shared@example.com')
        ->where('phone_number', '08030000001')
        ->where('username', 'shared-customer')
        ->exists())->toBeTrue();
});

