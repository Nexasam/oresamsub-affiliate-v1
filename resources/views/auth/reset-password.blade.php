@extends('oresamsub.layouts.authapp')
@section('title', 'Reset password · '.(session('affiliate')->name ?? 'Customer account'))
@section('content')
<x-customer-auth.shell title="Choose a new password" description="Secure your account with a new password and transaction PIN." :affiliate="session('affiliate')" :site-logo="$site_logo ?? null">
    <form method="POST" action="{{ route('password.store') }}" x-data="{ password: false, confirmPassword: false, pin: false, confirmPin: false, submitting: false }" @submit="submitting = true" class="auth-grid">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="auth-field"><label for="email">Email address</label><input class="auth-input" type="email" id="email" name="email" value="{{ old('email', $request->email) }}" autocomplete="email" required><x-input-error :messages="$errors->get('email')" class="auth-error" /></div>
        <div class="auth-field"><label for="password">New password</label><div class="auth-input-wrap"><input class="auth-input" :type="password ? 'text' : 'password'" id="password" name="password" autocomplete="new-password" required><button class="auth-reveal" type="button" @click="password = !password" x-text="password ? 'Hide' : 'Show'"></button></div><x-input-error :messages="$errors->get('password')" class="auth-error" /></div>
        <div class="auth-field"><label for="password_confirmation">Confirm password</label><div class="auth-input-wrap"><input class="auth-input" :type="confirmPassword ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required><button class="auth-reveal" type="button" @click="confirmPassword = !confirmPassword" x-text="confirmPassword ? 'Hide' : 'Show'"></button></div><x-input-error :messages="$errors->get('password_confirmation')" class="auth-error" /></div>
        <div class="auth-grid auth-grid--two">
            <div class="auth-field"><label for="new_pin">New transaction PIN</label><div class="auth-input-wrap"><input class="auth-input" :type="pin ? 'text' : 'password'" id="new_pin" name="new_pin" maxlength="4" inputmode="numeric" required><button class="auth-reveal" type="button" @click="pin = !pin" x-text="pin ? 'Hide' : 'Show'"></button></div><x-input-error :messages="$errors->get('new_pin')" class="auth-error" /></div>
            <div class="auth-field"><label for="new_pin_confirmation">Confirm PIN</label><div class="auth-input-wrap"><input class="auth-input" :type="confirmPin ? 'text' : 'password'" id="new_pin_confirmation" name="new_pin_confirmation" maxlength="4" inputmode="numeric" required><button class="auth-reveal" type="button" @click="confirmPin = !confirmPin" x-text="confirmPin ? 'Hide' : 'Show'"></button></div><x-input-error :messages="$errors->get('new_pin_confirmation')" class="auth-error" /></div>
        </div>
        <button class="auth-button" type="submit" :disabled="submitting"><span x-show="!submitting">Reset password</span><span x-show="submitting" x-cloak>Resetting password…</span></button>
    </form>
    <p class="auth-footer"><a class="auth-link" href="{{ route('login') }}">Back to login</a></p>
</x-customer-auth.shell>
@endsection
