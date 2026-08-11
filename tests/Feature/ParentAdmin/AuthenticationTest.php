<?php

use App\Models\Admin;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function parentAdminAccount(array $overrides = []): ParentAdmin
{
    $parent = $overrides['parent'] ?? ParentBusiness::create([
        'name' => 'OresamSub',
        'slug' => 'oresamsub',
    ]);
    unset($overrides['parent']);

    return ParentAdmin::create(array_merge([
        'parent_business_id' => $parent->id,
        'name' => 'OresamSub Owner',
        'email' => 'parent-owner@example.test',
        'password' => 'secret-password',
        'active' => true,
        'must_change_password' => false,
    ], $overrides));
}

it('redirects guests to the parent administrator login', function () {
    $this->get('/parent-admin')
        ->assertRedirect('/parent-admin/login');
});

it('allows an active parent administrator to sign in to its business workspace', function () {
    $admin = parentAdminAccount();

    $this->post('/parent-admin/login', [
        'email' => $admin->email,
        'password' => 'secret-password',
    ])->assertRedirect('/parent-admin');

    $this->assertAuthenticatedAs($admin, 'parent_admin');
    expect($admin->fresh()->last_login_at)->not->toBeNull();

    $this->get('/parent-admin')
        ->assertOk()
        ->assertSee('OresamSub')
        ->assertDontSee('Affiliate profit limits')
        ->assertSee('aria-hidden="true"', false)
        ->assertDontSee('>DB<', false)
        ->assertSee('Product plans')
        ->assertSee('Pricing');
});

it('rejects an inactive parent administrator', function () {
    $admin = parentAdminAccount(['active' => false]);

    $this->from('/parent-admin/login')->post('/parent-admin/login', [
        'email' => $admin->email,
        'password' => 'secret-password',
    ])->assertRedirect('/parent-admin/login')->assertSessionHasErrors('email');

    $this->assertGuest('parent_admin');
});

it('does not reuse an affiliate intended url after parent administrator login', function () {
    $admin = parentAdminAccount();

    $this->withSession(['url.intended' => '/login'])
        ->post('/parent-admin/login', [
            'email' => $admin->email,
            'password' => 'secret-password',
        ])->assertRedirect('/parent-admin');
});

it('keeps platform and parent administrator guards isolated', function () {
    $platformAdmin = Admin::create([
        'name' => 'Platform Owner',
        'email' => 'platform-owner@example.test',
        'password' => 'secret-password',
        'active' => true,
    ]);

    $this->actingAs($platformAdmin, 'platform_admin')
        ->get('/parent-admin')
        ->assertRedirect('/parent-admin/login');

    $this->assertGuest('parent_admin');
});

it('redirects an authenticated parent administrator away from login', function () {
    $admin = parentAdminAccount();

    $this->actingAs($admin, 'parent_admin')
        ->get('/parent-admin/login')
        ->assertRedirect('/parent-admin');
});

it('logs out only the parent administrator guard', function () {
    $admin = parentAdminAccount();

    $this->actingAs($admin, 'parent_admin')
        ->post('/parent-admin/logout')
        ->assertRedirect('/parent-admin/login');

    $this->assertGuest('parent_admin');
});
