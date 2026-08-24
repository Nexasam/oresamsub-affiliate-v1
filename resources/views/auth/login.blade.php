@extends('oresamsub.layouts.authapp')
@section('title', 'Login · '.(session('affiliate')->name ?? 'Customer account'))
@section('content')
<x-customer-auth.shell title="Welcome back" description="Sign in to manage your wallet and purchase services securely." :affiliate="session('affiliate')" :site-logo="$site_logo ?? null">
    <form method="POST" action="{{ route('inertia.login.store') }}" x-data="{ showPassword: false, submitting: false }" @submit="submitting = true" class="auth-grid">
        @csrf
        <div class="auth-field">
            <label for="email">Email address</label>
            <input class="auth-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" placeholder="you@example.com" required autofocus>
            <x-input-error :messages="$errors->get('email')" class="auth-error" />
        </div>
        <div class="auth-field">
            <div class="flex items-center justify-between gap-3"><label for="password">Password</label><a class="auth-link text-xs" href="{{ route('password.request') }}">Forgot password?</a></div>
            <div class="auth-input-wrap">
                <input class="auth-input" :type="showPassword ? 'text' : 'password'" id="password" name="password" autocomplete="current-password" required>
                <button class="auth-reveal" type="button" @click="showPassword = !showPassword" x-text="showPassword ? 'Hide' : 'Show'"></button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="auth-error" />
        </div>
        <button class="auth-button" type="submit" :disabled="submitting"><span x-show="!submitting">Sign in</span><span x-show="submitting" x-cloak>Signing in…</span></button>
    </form>
    <p class="auth-footer">New here? <a class="auth-link" href="{{ route('register') }}">Create an account</a></p>
</x-customer-auth.shell>
@endsection
