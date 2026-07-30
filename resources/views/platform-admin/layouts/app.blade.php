<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Platform Admin') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/platform-admin.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<div x-data="{ sidebar: false }" class="min-h-screen">
    <div x-cloak x-show="sidebar" x-transition.opacity @click="sidebar = false" class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"></div>

    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-slate-950 text-white transition-transform duration-200 lg:translate-x-0">
        <div class="flex h-20 items-center gap-3 border-b border-white/10 px-7">
            <div class="grid h-10 w-10 place-items-center rounded-2xl bg-emerald-400 font-black text-slate-950">O</div>
            <div>
                <p class="font-semibold leading-tight">Platform control</p>
                <p class="text-xs text-slate-400">All affiliate systems</p>
            </div>
        </div>
        <nav class="flex-1 space-y-2 p-4">
            <a href="{{ route('platform-admin.dashboard') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium {{ request()->routeIs('platform-admin.dashboard') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                <span>◫</span> Overview
            </a>
            <a href="{{ route('platform-admin.affiliates.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium {{ request()->routeIs('platform-admin.affiliates.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                <span>◎</span> Affiliates
            </a>
            @if (Route::has('platform-admin.catalog.index'))
                <a href="{{ route('platform-admin.catalog.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium {{ request()->routeIs('platform-admin.catalog.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <span>▦</span> Global catalog
                </a>
            @endif
            @if (Route::has('platform-admin.affiliate-catalog.index'))
                <a href="{{ route('platform-admin.affiliate-catalog.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium {{ request()->routeIs('platform-admin.affiliate-catalog.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <span>⌘</span> Affiliate catalog
                </a>
            @endif
            @if (Route::has('platform-admin.affiliate-users.index'))
                <a href="{{ route('platform-admin.affiliate-users.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium {{ request()->routeIs('platform-admin.affiliate-users.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <span>♙</span> Affiliate users
                </a>
            @endif
            @if (Route::has('platform-admin.operations.index'))
                <a href="{{ route('platform-admin.operations.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium {{ request()->routeIs('platform-admin.operations.*') || request()->routeIs('platform-admin.affiliates.operations') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <span>⚙</span> Operations
                </a>
            @endif
            @if (Route::has('platform-admin.transactions.index'))
                <a href="{{ route('platform-admin.transactions.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium {{ request()->routeIs('platform-admin.transactions.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <span>↔</span> Transactions
                </a>
            @endif
            @if (Route::has('platform-admin.reports.index'))
                <a href="{{ route('platform-admin.reports.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium {{ request()->routeIs('platform-admin.reports.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <span>↗</span> Reports & profit
                </a>
            @endif
        </nav>
        <div class="border-t border-white/10 p-4">
            <div class="mb-3 px-3">
                <p class="truncate text-sm font-medium">{{ auth('platform_admin')->user()->name }}</p>
                <p class="truncate text-xs text-slate-400">{{ auth('platform_admin')->user()->email }}</p>
            </div>
            <form method="POST" action="{{ route('platform-admin.logout') }}">
                @csrf
                <button class="w-full rounded-xl border border-white/10 px-4 py-2.5 text-left text-sm text-slate-300 hover:bg-white/5">Sign out</button>
            </form>
        </div>
    </aside>

    <main class="lg:pl-72">
        <header class="sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200/80 bg-white/90 px-5 backdrop-blur lg:px-10">
            <button @click="sidebar = true" class="rounded-xl border border-slate-200 p-2 lg:hidden">☰</button>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[.2em] text-emerald-600">@yield('eyebrow', 'Platform')</p>
                <h1 class="text-lg font-semibold">@yield('heading', 'Overview')</h1>
            </div>
            <div class="hidden items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 sm:flex">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Platform access
            </div>
        </header>
        <div class="p-5 lg:p-10">
            @yield('content')
        </div>
    </main>
</div>
@stack('scripts')
</body>
</html>
