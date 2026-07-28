<?php

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests away from the platform dashboard', function () {
    $this->get('/admin')
        ->assertRedirect('/admin/login');
});

it('allows an active platform administrator to sign in', function () {
    Admin::create([
        'name' => 'Platform Owner',
        'email' => 'owner@example.com',
        'password' => 'secret-password',
        'active' => true,
    ]);

    $this->post('/admin/login', [
        'email' => 'owner@example.com',
        'password' => 'secret-password',
    ])->assertRedirect('/admin');

    $this->assertAuthenticated('platform_admin');
});

it('does not redirect a platform administrator to an affiliate intended url', function () {
    Admin::create([
        'name' => 'Platform Owner',
        'email' => 'owner@example.com',
        'password' => 'secret-password',
        'active' => true,
    ]);

    $this->withSession(['url.intended' => '/login'])
        ->post('/admin/login', [
            'email' => 'owner@example.com',
            'password' => 'secret-password',
        ])
        ->assertRedirect('/admin');
});

it('redirects an authenticated platform administrator away from login to the platform dashboard', function () {
    $admin = Admin::create([
        'name' => 'Platform Owner',
        'email' => 'owner@example.com',
        'password' => 'secret-password',
        'active' => true,
    ]);

    $this->actingAs($admin, 'platform_admin')
        ->get('/admin/login')
        ->assertRedirect('/admin');
});

it('rejects inactive platform administrators', function () {
    Admin::create([
        'name' => 'Inactive Owner',
        'email' => 'inactive@example.com',
        'password' => 'secret-password',
        'active' => false,
    ]);

    $this->from('/admin/login')->post('/admin/login', [
        'email' => 'inactive@example.com',
        'password' => 'secret-password',
    ])->assertRedirect('/admin/login')->assertSessionHasErrors('email');

    $this->assertGuest('platform_admin');
});
