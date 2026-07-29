<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Platform sign in · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/platform-admin.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-900 antialiased">
<main class="grid min-h-screen lg:grid-cols-2">
    <section class="hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-emerald-400 via-teal-500 to-cyan-700 p-14 text-white lg:flex">
        <div class="grid h-12 w-12 place-items-center rounded-2xl bg-white/20 text-xl font-black backdrop-blur">O</div>
        <div class="max-w-lg">
            <p class="mb-5 text-sm font-semibold uppercase tracking-[.25em] text-emerald-50">One platform. Every tenant.</p>
            <h1 class="text-5xl font-bold leading-tight">A clearer view of your entire affiliate networkkk</h1>
            <p class="mt-6 text-lg text-white/80">Manage affiliates, their people, transactions and operational settings from one secure workspace.</p>
        </div>
        <p class="text-sm text-white/60">{{ config('app.name') }} platform operations</p>
    </section>
    <section class="flex items-center justify-center bg-white px-6 py-12">
        <div class="w-full max-w-md">
            <div class="mb-10 lg:hidden">
                <div class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-500 font-black text-white">O</div>
            </div>
            <p class="text-sm font-semibold text-emerald-600">PLATFORM ADMINISTRATION</p>
            <h2 class="mt-2 text-3xl font-bold tracking-tight">Welcome back</h2>
            <p class="mt-2 text-slate-500">Sign in with your platform administrator account.</p>

            <form method="POST" action="{{ route('platform-admin.login.store') }}" class="mt-9 space-y-5">
                @csrf
                <label class="block">
                    <span class="mb-2 block text-sm font-medium">Email address</span>
                    <input name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="w-full rounded-xl border-slate-200 px-4 py-3.5 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @error('email') <span class="mt-2 block text-sm text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-medium">Password</span>
                    <input name="password" type="password" required autocomplete="current-password" class="w-full rounded-xl border-slate-200 px-4 py-3.5 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </label>
                <label class="flex items-center gap-3 text-sm text-slate-600">
                    <input name="remember" type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Keep me signed in
                </label>
                <button class="w-full rounded-xl bg-slate-950 px-5 py-3.5 font-semibold text-white shadow-lg shadow-slate-950/10 transition hover:bg-emerald-600">Sign in securely</button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
