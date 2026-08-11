<?php

use Illuminate\Support\Facades\Hash;

test('affiliate user profile page is displayed', function () {
    $user = affiliateTestContext()['user'];

    $this->actingAs($user)->get('/profile')->assertOk();
});

test('affiliate user can update their password from the profile', function () {
    $user = affiliateTestContext()['user'];

    $this->actingAs($user)->from('/profile')->post('/profile/password', [
        'current_password' => 'password',
        'new_password' => 'new-password',
        'new_password_confirmation' => 'new-password',
    ])->assertSessionHasNoErrors()->assertRedirect('/profile');

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('affiliate profile rejects an incorrect current password', function () {
    $user = affiliateTestContext()['user'];

    $this->actingAs($user)->from('/profile')->post('/profile/password', [
        'current_password' => 'wrong-password',
        'new_password' => 'new-password',
        'new_password_confirmation' => 'new-password',
    ])->assertSessionHasErrors('current_password')->assertRedirect('/profile');
});

test('affiliate user can update their transaction pin', function () {
    $user = affiliateTestContext()['user'];

    $this->actingAs($user)->from('/profile')->post('/profile/pin', [
        'current_pin' => '4321',
        'new_pin' => '5678',
        'new_pin_confirmation' => '5678',
    ])->assertSessionHasNoErrors()->assertRedirect('/profile');

    expect($user->refresh()->pin)->toBe('5678');
});
