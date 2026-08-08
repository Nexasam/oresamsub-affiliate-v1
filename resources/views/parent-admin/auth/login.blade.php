<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Parent administrator sign in · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/platform-admin.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-900 antialiased">
<main class="grid min-h-screen lg:grid-cols-2">
    <section class="hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-indigo-500 via-blue-600 to-slate-950 p-14 text-white lg:flex">
        <div class="grid h-12 w-12 place-items-center rounded-2xl bg-white/15 text-xl font-black backdrop-blur">P</div>
        <div class="max-w-lg">
            <p class="mb-5 text-sm font-semibold uppercase tracking-[.25em] text-blue-100">Your affiliate network</p>
            <h1 class="text-5xl font-bold leading-tight">Manage your products and reseller pricing</h1>
            <p class="mt-6 text-lg text-white/75">A secure workspace dedicated to your business and the affiliates connected to it.</p>
        </div>
        <p class="text-sm text-white/50">{{ config('app.name') }} parent operations</p>
    </section>
    <section class="flex items-center justify-center bg-white px-6 py-12">
        <div class="w-full max-w-md">
            <div class="mb-10 grid h-12 w-12 place-items-center rounded-2xl bg-blue-600 font-black text-white lg:hidden">P</div>
            <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Parent administration</p>
            <h2 class="mt-2 text-3xl font-bold tracking-tight">Welcome back</h2>
            <p class="mt-2 text-slate-500">Sign in with your parent administrator account.</p>

            <form method="POST" action="{{ route('parent-admin.login.store') }}" class="mt-9 space-y-5">
                @csrf
                <label class="block">
                    <span class="mb-2 block text-sm font-medium">Email address</span>
                    <input name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="w-full rounded-xl border-slate-200 px-4 py-3.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('email') <span class="mt-2 block text-sm text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-medium">Password</span>
                    <input name="password" type="password" required autocomplete="current-password" class="w-full rounded-xl border-slate-200 px-4 py-3.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </label>
                <label class="flex items-center gap-3 text-sm text-slate-600">
                    <input name="remember" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"> Keep me signed in
                </label>
                <button class="w-full rounded-xl bg-slate-950 px-5 py-3.5 font-semibold text-white shadow-lg shadow-slate-950/10 transition hover:bg-blue-600">Sign in securely</button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
