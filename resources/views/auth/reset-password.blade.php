@extends('oresamsub.layouts.authapp')

@section('content')

<div 
    class="relative min-h-screen w-full flex items-center justify-center overflow-hidden"
    x-data="{ 
        showPassword: false,
        showConfirmPassword: false,
        showPin: false,
        showConfirmPin: false,
        isResetting: false
    }"
>

    {{-- Background Pattern --}}
    <div class="absolute inset-0 z-0 pointer-events-none">
        <div class="w-full h-full 
            bg-gray-50 dark:bg-gray-900 
            bg-[radial-gradient(circle,_rgba(0,0,0,0.05)_1px,_transparent_1px)] 
            dark:bg-[radial-gradient(circle,_rgba(255,255,255,0.05)_1px,_transparent_1px)] 
            [background-size:22px_22px]">
        </div>
    </div>

    {{-- Reset Card --}}
    <div 
        class="relative z-10 pt-10 pb-6 max-w-full w-full mx-auto bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-t-4"
        style="border-color: {{ session('user_dashboard_primary_color', '#0d6efd') }}"
    >

        {{-- Logo --}}
        <div class="flex justify-center mb-4">

            @if(isset($site_logo) && $site_logo)

                <img
                    src="{{ asset('assets/landing_page_assets/img/site_logo/'.$site_logo) }}"
                    alt="{{ session('affiliate')->name ?? 'Logo' }}"
                    class="h-20 w-20 rounded-full shadow-md object-cover"
                >

            @else

                <div
                    class="h-20 w-20 rounded-full shadow-md flex items-center justify-center text-white font-bold text-2xl uppercase"
                    style="background-color: {{ session('user_dashboard_primary_color', '#0d6efd') }}"
                >
                    {{ substr(session('affiliate')->name ?? 'A', 0, 1) }}
                </div>

            @endif

        </div>

        {{-- Heading --}}
        <h2 class="text-2xl mt-3 font-bold text-center mb-2 text-gray-900 dark:text-white">
            Reset Your Password
        </h2>

        <p class="text-sm text-center text-gray-500 dark:text-gray-400 mb-6">
            Create a new password and transaction PIN for your account.
        </p>

        {{-- Flash Messages --}}
        <div class="cols-span-1">

            @if(Session::has('success'))
                <div class="bg-success/10 border border-success/10 alert text-success mb-4" role="alert">
                    {{ Session::get('success') }}
                </div>
            @endif

            @if(Session::has('failure'))
                <div class="bg-danger/10 border border-danger/10 alert text-danger mb-4" role="alert">
                    {{ Session::get('failure') }}
                </div>
            @endif

            @if(Session::has('error'))
                <div class="bg-danger/10 border border-danger/10 alert text-danger mb-4" role="alert">
                    {{ Session::get('error') }}
                </div>
            @endif

        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        {{-- Form --}}
        <form 
            method="POST" 
            action="{{ route('password.store') }}"
            @submit.prevent="isResetting = true; $el.submit();"
        >
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            {{-- Email --}}
            <div class="mb-4">
                <label for="email" class="block text-sm mb-1">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', $request->email) }}"
                    required
                    autofocus
                    autocomplete="username"
                    class="w-full px-4 py-2 rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            {{-- Password --}}
            <div class="mb-4">

                <label for="password" class="block text-sm mb-1">
                    New Password
                </label>

                <div class="relative">

                    <input
                        :type="showPassword ? 'text' : 'password'"
                        id="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        class="w-full px-4 py-2 pr-10 rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >

                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500"
                    >
                        <span x-text="showPassword ? '🙈' : '👁️'"></span>
                    </button>

                </div>

                <x-input-error :messages="$errors->get('password')" class="mt-1" />

            </div>

            {{-- Confirm Password --}}
            <div class="mb-4">

                <label for="password_confirmation" class="block text-sm mb-1">
                    Confirm Password
                </label>

                <div class="relative">

                    <input
                        :type="showConfirmPassword ? 'text' : 'password'"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="w-full px-4 py-2 pr-10 rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >

                    <button
                        type="button"
                        @click="showConfirmPassword = !showConfirmPassword"
                        class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500"
                    >
                        <span x-text="showConfirmPassword ? '🙈' : '👁️'"></span>
                    </button>

                </div>

                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />

            </div>

            {{-- New PIN --}}
            <div class="mb-4">

                <label for="new_pin" class="block text-sm mb-1">
                    New Transaction PIN
                </label>

                <div class="relative">

                    <input
                        :type="showPin ? 'text' : 'password'"
                        id="new_pin"
                        name="new_pin"
                        required
                        maxlength="4"
                        placeholder="Enter 4 digit PIN"
                        class="w-full px-4 py-2 pr-10 rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >

                    <button
                        type="button"
                        @click="showPin = !showPin"
                        class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500"
                    >
                        <span x-text="showPin ? '🙈' : '👁️'"></span>
                    </button>

                </div>

                <x-input-error :messages="$errors->get('new_pin')" class="mt-1" />

            </div>

            {{-- Confirm PIN --}}
            <div class="mb-6">

                <label for="new_pin_confirmation" class="block text-sm mb-1">
                    Confirm Transaction PIN
                </label>

                <div class="relative">

                    <input
                        :type="showConfirmPin ? 'text' : 'password'"
                        id="new_pin_confirmation"
                        name="new_pin_confirmation"
                        required
                        maxlength="5"
                        placeholder="Confirm PIN"
                        class="w-full px-4 py-2 pr-10 rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >

                    <button
                        type="button"
                        @click="showConfirmPin = !showConfirmPin"
                        class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500"
                    >
                        <span x-text="showConfirmPin ? '🙈' : '👁️'"></span>
                    </button>

                </div>

                <x-input-error :messages="$errors->get('new_pin_confirmation')" class="mt-1" />

            </div>

            {{-- Submit Button --}}
            <div class="mt-6">

                <button
                    type="submit"
                    :disabled="isResetting"
                    class="w-full py-2 px-4 text-white rounded-lg transition disabled:opacity-50 shadow hover:shadow-md"
                    style="
                        background-color: {{ session('user_dashboard_primary_color', '#0d6efd') }};
                        border: 2px solid {{ session('user_dashboard_primary_color', '#0d6efd') }};
                        box-shadow: 0 0 6px {{ session('user_dashboard_primary_color', '#0d6efd') }}40;
                    "
                >

                    <span x-show="!isResetting">
                        🔐 Reset Password
                    </span>

                    <span 
                        x-show="isResetting"
                        x-cloak
                        class="flex items-center justify-center gap-2"
                    >

                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                                fill="none"
                            />

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8z"
                            />
                        </svg>

                        Resetting...

                    </span>

                </button>

            </div>

        </form>

        {{-- Back to Login --}}
        <p 
            class="text-xs text-center mt-6 text-gray-500 dark:text-gray-400"
            x-data="{ loading: false }"
        >

            Remember your password?

            <a
                href="{{ route('login') }}"
                @click.prevent="loading = true; setTimeout(() => window.location.href = '{{ route('login') }}', 300)"
                class="font-semibold transition"
                x-show="!loading"
                style="
                    color: {{ session('user_dashboard_primary_color', '#0d6efd') }};
                    text-shadow: 0 0 4px {{ session('user_dashboard_primary_color', '#0d6efd') }}33;
                "
            >
                Login
            </a>

            <span
                x-show="loading"
                x-cloak
                class="font-semibold flex justify-center items-center gap-1"
                style="color: {{ session('user_dashboard_primary_color', '#0d6efd') }}"
            >

                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    />

                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8z"
                    />
                </svg>

                Redirecting...

            </span>

        </p>

    </div>

</div>

@endsection