<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Parent Admin') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/platform-admin.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
@php($parentAdmin = auth('parent_admin')->user()->loadMissing('parentBusiness'))
<div x-data="{ sidebar: false }" class="min-h-screen">
    <div x-cloak x-show="sidebar" x-transition.opacity @click="sidebar = false" class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"></div>
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-slate-950 text-white transition-transform duration-200 lg:translate-x-0">
        <div class="flex h-20 items-center gap-3 border-b border-white/10 px-7">
            <div class="grid h-10 w-10 place-items-center rounded-2xl bg-blue-400 font-black text-slate-950">P</div>
            <div class="min-w-0"><p class="truncate font-semibold leading-tight">{{ $parentAdmin->parentBusiness->name }}</p><p class="text-xs text-slate-400">Parent workspace</p></div>
        </div>
        @php($navigation = [
            ['label' => 'Dashboard', 'route' => 'parent-admin.dashboard', 'match' => 'parent-admin.dashboard', 'icon' => 'dashboard'],
            ['label' => 'Operations', 'route' => 'parent-admin.operations.index', 'match' => 'parent-admin.operations.*', 'icon' => 'activity'],
            ['label' => 'Transactions', 'route' => 'parent-admin.transactions.index', 'match' => 'parent-admin.transactions.*', 'icon' => 'activity'],
            ['label' => 'Affiliates', 'route' => 'parent-admin.affiliates.index', 'match' => 'parent-admin.affiliates.*', 'icon' => 'users'],
            ['label' => 'Product plans', 'route' => 'parent-admin.product-plans.index', 'match' => 'parent-admin.product-plans.*', 'icon' => 'catalog'],
            ['label' => 'Pricing levels', 'route' => 'parent-admin.pricing.index', 'match' => ['parent-admin.pricing.index', 'parent-admin.pricing.data', 'parent-admin.pricing.defaults.*', 'parent-admin.pricing.levels.*', 'parent-admin.pricing.plans.*', 'parent-admin.pricing.affiliates.*'], 'icon' => 'pricing'],
            ['label' => 'Provider connections', 'route' => 'parent-admin.provider-connections.index', 'match' => 'parent-admin.provider-connections.*', 'icon' => 'connection'],
            ['label' => 'Funding providers', 'route' => 'parent-admin.funding-providers.index', 'match' => ['parent-admin.funding-providers.*', 'parent-admin.funding-mode-requests.*'], 'icon' => 'wallet'],
        ])
        <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-4">
            @foreach($navigation as $item)
            @php($active = request()->routeIs($item['match']))
            <a href="{{ route($item['route']) }}" class="group flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-medium transition {{ $active ? 'bg-blue-400 text-slate-950' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-md {{ $active ? 'bg-slate-950/10' : 'bg-white/5 text-slate-300' }}">
                    @switch($item['icon'])
                        @case('dashboard')<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>@break
                        @case('activity')<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M3 12h4l2.5-7 5 14 2.5-7h4"/></svg>@break
                        @case('users')<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>@break
                        @case('catalog')<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>@break
                        @case('pricing')<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><circle cx="12" cy="12" r="9"/><path d="M8.5 9.5h6a2 2 0 0 1 0 4h-5a2 2 0 0 0 0 4h6M12 6.5v11"/></svg>@break
                        @case('connection')<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>@break
                        @case('wallet')<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M20 7V5a2 2 0 0 0-2-2H5a3 3 0 0 0 0 6h16v10a2 2 0 0 1-2 2H5a3 3 0 0 1-3-3V6"/><path d="M16 14h2"/></svg>@break
                    @endswitch
                </span>
                <span class="truncate">{{ $item['label'] }}</span>
            </a>
            @endforeach
        </nav>
        <div class="border-t border-white/10 p-4">
            <div class="mb-3 px-3"><p class="truncate text-sm font-medium">{{ $parentAdmin->name }}</p><p class="truncate text-xs text-slate-400">{{ $parentAdmin->email }}</p></div>
            <form method="POST" action="{{ route('parent-admin.logout') }}">@csrf<button class="w-full rounded-xl border border-white/10 px-4 py-2.5 text-left text-sm text-slate-300 hover:bg-white/5">Sign out</button></form>
        </div>
    </aside>
    <main class="lg:pl-72">
        <header class="sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200/80 bg-white/90 px-5 backdrop-blur lg:px-10">
            <button @click="sidebar = true" class="rounded-xl border border-slate-200 p-2 lg:hidden">☰</button>
            <div><p class="text-xs font-semibold uppercase tracking-[.2em] text-blue-600">{{ $parentAdmin->parentBusiness->name }}</p><h1 class="text-lg font-semibold">@yield('heading', 'Product plans')</h1></div>
            <div class="hidden items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 sm:flex"><span class="h-2 w-2 rounded-full bg-blue-500"></span> Parent access</div>
        </header>
        <div class="p-5 lg:p-10">@yield('content')</div>
    </main>
</div>
@stack('scripts')
</body>
</html>
