@extends('oresamsub.layouts.authapp')

@section('content')

<div 
    class="relative min-h-screen w-full flex items-center justify-center overflow-hidden"
    x-data="{ 
        isSending: false
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

    {{-- Forgot Password Card --}}
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
        <div class="text-center">

            <h2 class="text-2xl mt-3 font-bold text-gray-900 dark:text-white">
                Forgot Password
            </h2>

            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                Forgot your password? No problem.
                Enter your email address below and we’ll send you a password reset link.
            </p>

        </div>

        {{-- Flash Messages --}}
        <div class="mt-5">

            <x-auth-session-status class="mb-4" :status="session('status')" />

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

        {{-- Form --}}
        <form 
            method="POST" 
            action="{{ route('password.email') }}"
            @submit.prevent="isSending = true; $el.submit();"
        >

            @csrf

            {{-- Email --}}
            <div class="mb-4">

                <label for="email" class="block text-sm mb-1">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="Enter your email address"
                    class="w-full px-4 py-2 rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

                <x-input-error :messages="$errors->get('email')" class="mt-2" />

            </div>

            {{-- Submit Button --}}
            <div class="mt-6">

                <button
                    type="submit"
                    :disabled="isSending"
                    class="w-full py-2 px-4 text-white rounded-lg transition disabled:opacity-50 shadow hover:shadow-md"
                    style="
                        background-color: {{ session('user_dashboard_primary_color', '#0d6efd') }};
                        border: 2px solid {{ session('user_dashboard_primary_color', '#0d6efd') }};
                        box-shadow: 0 0 6px {{ session('user_dashboard_primary_color', '#0d6efd') }}40;
                    "
                >

                    <span x-show="!isSending">
                        📩 Email Password Reset Link
                    </span>

                    <span 
                        x-show="isSending"
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

                        Sending Link...

                    </span>

                </button>

            </div>

        </form>

        {{-- Back To Login --}}
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
                Return to login
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

