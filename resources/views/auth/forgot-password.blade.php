@extends('oresamsub.layouts.authapp')
@section('title', 'Forgot password · '.(session('affiliate')->name ?? 'Customer account'))
@section('content')
<x-customer-auth.shell title="Forgot your password?" description="Enter your account email and we’ll send you a secure reset link." :affiliate="session('affiliate')" :site-logo="$site_logo ?? null">
    <form method="POST" action="{{ route('password.email') }}" x-data="{ submitting: false }" @submit="submitting = true" class="auth-grid">
        @csrf
        <div class="auth-field"><label for="email">Email address</label><input class="auth-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" placeholder="you@example.com" required autofocus><x-input-error :messages="$errors->get('email')" class="auth-error" /></div>
        <button class="auth-button" type="submit" :disabled="submitting"><span x-show="!submitting">Send reset link</span><span x-show="submitting" x-cloak>Sending link…</span></button>
    </form>
    <p class="auth-footer">Remembered it? <a class="auth-link" href="{{ route('login') }}">Back to login</a></p>
</x-customer-auth.shell>
@endsection
