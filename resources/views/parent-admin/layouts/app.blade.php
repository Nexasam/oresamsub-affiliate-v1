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
            ['label' => 'Overview', 'items' => [
                ['label' => 'Dashboard', 'route' => 'parent-admin.dashboard', 'match' => 'parent-admin.dashboard', 'icon' => 'DB'],
                ['label' => 'Operations', 'route' => 'parent-admin.operations.index', 'match' => 'parent-admin.operations.*', 'icon' => 'OP'],
            ]],
            ['label' => 'Affiliate network', 'items' => [
                ['label' => 'Affiliates', 'route' => 'parent-admin.affiliates.index', 'match' => 'parent-admin.affiliates.*', 'icon' => 'AF'],
                ['label' => 'Affiliate profit limits', 'route' => 'parent-admin.pricing.affiliates.index', 'match' => 'parent-admin.pricing.affiliates.*', 'icon' => 'PL'],
            ]],
            ['label' => 'Products & pricing', 'items' => [
                ['label' => 'Product plans', 'route' => 'parent-admin.product-plans.index', 'match' => 'parent-admin.product-plans.*', 'icon' => 'PP'],
                ['label' => 'Pricing levels', 'route' => 'parent-admin.pricing.index', 'match' => ['parent-admin.pricing.index', 'parent-admin.pricing.data', 'parent-admin.pricing.defaults.*', 'parent-admin.pricing.levels.*', 'parent-admin.pricing.plans.*'], 'icon' => '₦'],
            ]],
            ['label' => 'Integrations', 'items' => [
                ['label' => 'Provider connections', 'route' => 'parent-admin.provider-connections.index', 'match' => 'parent-admin.provider-connections.*', 'icon' => 'PC'],
                ['label' => 'Funding providers', 'route' => 'parent-admin.funding-providers.index', 'match' => ['parent-admin.funding-providers.*', 'parent-admin.funding-mode-requests.*'], 'icon' => 'FP'],
            ]],
        ])
        <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-5">
            @foreach($navigation as $group)
            <section>
                <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-slate-500">{{ $group['label'] }}</p>
                <div class="space-y-1">
                    @foreach($group['items'] as $item)
                    @php($active = request()->routeIs($item['match']))
                    <a href="{{ route($item['route']) }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-blue-400 text-slate-950 shadow-sm' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-[10px] font-black {{ $active ? 'bg-slate-950/10' : 'bg-white/5 text-slate-300 group-hover:bg-white/10' }}">{{ $item['icon'] }}</span>
                        <span class="truncate">{{ $item['label'] }}</span>
                    </a>
                    @endforeach
                </div>
            </section>
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
