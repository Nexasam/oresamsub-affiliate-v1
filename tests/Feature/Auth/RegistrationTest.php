<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    affiliateTestContext();
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    affiliateTestContext();
    $response = $this->post('/register', [
        'username' => 'test-user',
        'first_name' => 'Test',
        'last_name' => 'User',
        'pin' => '4321',
        'phone_number' => '08035550999',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('modern registration returns clear tenant scoped duplicate errors instead of a server error', function () {
    $context = affiliateTestContext();
    User::factory()->create([
        'affiliate_id' => $context['affiliate']->id,
        'role_id' => $context['role']->id,
        'user_plan_id' => $context['plan']->id,
        'username' => 'existing-customer',
        'phone_number' => '08035550111',
        'email' => 'existing@example.com',
    ]);

    $response = $this->from('/register')->post('/register2', [
        'fullname' => 'Existing Customer',
        'username' => 'existing-customer',
        'phone_number' => '08035550111',
        'email' => 'existing@example.com',
        'pin' => '4321',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect('/register')->assertSessionHasErrors([
        'username' => 'This username already has an account on this website.',
        'phone_number' => 'This phone number already has an account on this website.',
        'email' => 'This email address already has an account on this website.',
    ]);
    $this->assertGuest();
});

test('the same customer identifiers can register on another affiliate and submitted affiliate ids are ignored', function () {
    Notification::fake();
    $first = affiliateTestContext();
    User::factory()->create([
        'affiliate_id' => $first['affiliate']->id,
        'role_id' => $first['role']->id,
        'user_plan_id' => $first['plan']->id,
        'username' => 'shared-customer',
        'phone_number' => '08035550222',
        'email' => 'shared@example.com',
    ]);
    $second = affiliateTestContext();

    $response = $this->post('/register2', [
        'affiliate_id' => $first['affiliate']->id,
        'fullname' => 'Shared Customer',
        'username' => 'shared-customer',
        'phone_number' => '08035550222',
        'email' => 'shared@example.com',
        'pin' => '4321',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('verification.notice'));
    $this->assertDatabaseHas('users', [
        'affiliate_id' => $second['affiliate']->id,
        'username' => 'shared-customer',
        'phone_number' => '08035550222',
        'email' => 'shared@example.com',
    ]);
});

test('legacy registration also validates duplicate phone numbers within the current affiliate', function () {
    $context = affiliateTestContext();
    User::factory()->create([
        'affiliate_id' => $context['affiliate']->id,
        'role_id' => $context['role']->id,
        'user_plan_id' => $context['plan']->id,
        'username' => 'legacy-existing',
        'phone_number' => '08035550333',
        'email' => 'legacy-existing@example.com',
    ]);

    $this->from('/register')->post('/register', [
        'username' => 'legacy-new-name',
        'first_name' => 'Legacy',
        'last_name' => 'Customer',
        'phone_number' => '08035550333',
        'email' => 'legacy-new@example.com',
        'pin' => '4321',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect('/register')->assertSessionHasErrors([
        'phone_number' => 'This phone number already has an account on this website.',
    ]);
});
