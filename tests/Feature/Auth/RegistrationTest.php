<?php

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
