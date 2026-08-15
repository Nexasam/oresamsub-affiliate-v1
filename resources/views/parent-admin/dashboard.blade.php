@extends('parent-admin.layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
<div class="workspace-stack">
    <x-workspace.page-header title="{{ auth('parent_admin')->user()->parentBusiness->name }} workspace" description="Manage affiliates, catalogue, pricing, settlement and provider operations from one place.">
        <a href="{{ route('parent-admin.onboarding.index') }}" class="workspace-btn-secondary">View onboarding</a>
    </x-workspace.page-header>
    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Affiliates', route('parent-admin.affiliates.index'), 'Manage ownership and reseller levels.'],
            ['Transactions', route('parent-admin.transactions.index'), 'Review purchases and manual fulfilment.'],
            ['Product plans', route('parent-admin.product-plans.index'), 'Manage catalogue, cost and routing.'],
            ['Settlement wallets', route('parent-admin.settlement-wallets.index'), 'Track available and reserved funds.'],
        ] as [$label, $url, $description])
            <a href="{{ $url }}" class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-300 hover:shadow dark:border-slate-700 dark:bg-slate-900 dark:hover:border-blue-600">
                <div class="flex items-start justify-between gap-3"><h3 class="font-semibold">{{ $label }}</h3><span class="text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-blue-600">→</span></div>
                <p class="mt-2 text-sm leading-5 text-slate-500 dark:text-slate-400">{{ $description }}</p>
            </a>
        @endforeach
    </section>
    <section class="workspace-panel">
        <div class="workspace-panel-header"><div><h3 class="font-semibold">Workspace setup</h3><p class="mt-1 text-sm text-slate-500">The settings most often needed during onboarding and daily operations.</p></div></div>
        <div class="grid divide-y divide-slate-100 dark:divide-slate-800 sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-4">
            @foreach([
                ['Provider connections', route('parent-admin.provider-connections.index')],
                ['Pricing levels', route('parent-admin.pricing.index')],
                ['Funding providers', route('parent-admin.funding-providers.index')],
                ['Profit management', route('parent-admin.profits.index')],
            ] as [$label, $url])
                <a href="{{ $url }}" class="flex items-center justify-between gap-3 px-5 py-4 text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-800/60"><span>{{ $label }}</span><span class="text-slate-400">→</span></a>
            @endforeach
        </div>
    </section>
</div>
@endsection
