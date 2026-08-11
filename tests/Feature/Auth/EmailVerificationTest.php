<?php

use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('an unverified affiliate user can request a verification notification', function () {
    Notification::fake();
    $user = affiliateTestContext(['email_verified_at' => null])['user'];

    $response = $this->actingAs($user)->post('/email/verification-notification');

    $response->assertRedirect();
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('email can be verified', function () {
    $user = affiliateTestContext(['email_verified_at' => null])['user'];

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $user = affiliateTestContext(['email_verified_at' => null])['user'];

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
