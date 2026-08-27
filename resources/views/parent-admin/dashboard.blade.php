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
    <section class="workspace-panel" x-data="{
        switcher: null,
        selectedConnectionId: '',
        providerPlanId: '',
        openSwitcher(data) {
            this.switcher = data;
            const preferred = data.options.find(option => !option.current) || data.options[0];
            this.selectedConnectionId = preferred ? String(preferred.connection_id) : '';
            this.providerPlanId = preferred?.provider_plan_id || '';
        },
        selectRoute() {
            const option = this.switcher?.options.find(item => String(item.connection_id) === String(this.selectedConnectionId));
            this.providerPlanId = option?.provider_plan_id || '';
        }
    }">
        <div class="workspace-panel-header">
            <div><div class="flex items-center gap-2"><h3 class="font-semibold">Plan health alerts</h3>@if($healthNotificationCount)<span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-700">{{ $healthNotificationCount }} unread</span>@endif</div><p class="mt-1 text-sm text-slate-500">Alerts appear after 3 failures in 30 minutes or 5 within 24 hours. Successful reconciliations are excluded.</p></div>
            <a href="{{ route('parent-admin.transactions.index', ['routing_status' => 'failed']) }}" class="workspace-btn-secondary">View failed transactions</a>
        </div>
        @if($planHealthAlerts->isEmpty())
            <div class="px-5 py-8 text-center text-sm text-slate-500">No recent plan failures need attention.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800/60"><tr><th class="px-4 py-3">Plan</th><th class="px-4 py-3">Impact</th><th class="px-4 py-3">Latest reason</th><th class="px-4 py-3">Provider</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($planHealthAlerts as $alert)
                        <tr>
                            <td class="px-4 py-3"><div class="font-semibold">{{ $alert->plan->product_plan_name }}</div><div class="text-xs text-slate-500">{{ $alert->service }}</div></td>
                            <td class="px-4 py-3"><div class="font-semibold text-rose-600">{{ $alert->failure_count }} recent {{ Str::plural('failure', $alert->failure_count) }}</div><div class="text-xs text-slate-500">{{ $alert->affiliate?->name ?: 'Unknown affiliate' }}</div></td>
                            <td class="max-w-md px-4 py-3 text-slate-600 dark:text-slate-300">{{ Str::limit($alert->failure_reason, 150) }}</td>
                            <td class="px-4 py-3"><div class="font-medium">{{ $alert->connection?->name ?: 'Connection unavailable' }}</div>@if($alert->provider_website)<a class="text-xs text-blue-600 hover:underline" href="{{ $alert->provider_website }}" target="_blank" rel="noopener noreferrer">Provider dashboard ↗</a>@endif</td>
                            <td class="px-4 py-3"><div class="flex flex-wrap justify-end gap-2"><a class="workspace-btn-secondary" href="{{ route('parent-admin.transactions.index', ['reference' => $alert->transaction->txn_reference]) }}">Review</a><button type="button" class="workspace-btn-primary" @disabled($alert->route_options->isEmpty()) @click="openSwitcher({{ Js::from(['plan_name' => $alert->plan->product_plan_name, 'url' => route('parent-admin.product-plans.routes.switch', $alert->plan), 'options' => $alert->route_options->all()]) }})">Switch provider</button><form method="POST" action="{{ route('parent-admin.product-plans.disable', $alert->plan) }}" onsubmit="return confirm('Disable this plan for every affiliate? Existing affiliate prices and preferences will be preserved.');">@csrf @method('PATCH')<button class="workspace-btn-danger" type="submit" @disabled(! $alert->plan->visibility)>Disable plan</button></form></div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div x-cloak x-show="switcher" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <button type="button" class="absolute inset-0 bg-slate-950/50" aria-label="Close provider switcher" @click="switcher=null"></button>
            <aside class="absolute inset-y-0 right-0 w-full max-w-md overflow-y-auto bg-white p-6 shadow-2xl dark:bg-slate-900">
                <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-wide text-blue-600">Switch provider route</p><h3 class="mt-1 text-lg font-semibold" x-text="switcher?.plan_name"></h3></div><button type="button" class="rounded-lg border px-3 py-1.5 text-sm" @click="switcher=null">Close</button></div>
                <p class="mt-4 text-sm text-slate-500">Previously configured plan IDs are remembered. A first-time connection needs its provider plan ID once.</p>
                <form method="POST" :action="switcher?.url" class="mt-6 space-y-4" onsubmit="return confirm('Switch this plan to the selected provider now? New purchases will use the new route immediately.');">
                    @csrf @method('PATCH')
                    <label class="block text-sm font-medium">Provider connection
                        <select name="parent_provider_connection_id" x-model="selectedConnectionId" @change="selectRoute()" required class="mt-1 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                            <template x-for="option in switcher?.options || []" :key="option.connection_id">
                                <option :value="option.connection_id" x-text="option.connection_name + (option.provider_name ? ' · '+option.provider_name : '') + (option.current ? ' · Current' : option.ready ? ' · Ready' : ' · Setup required')"></option>
                            </template>
                        </select>
                    </label>
                    <label class="block text-sm font-medium">Provider external plan ID
                        <input name="provider_plan_id" x-model="providerPlanId" required maxlength="255" class="mt-1 w-full rounded-xl border-slate-300 font-mono dark:border-slate-700 dark:bg-slate-800" placeholder="Enter the ID expected by this provider">
                    </label>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">This changes only future purchases. Existing transactions and saved mappings remain unchanged.</div>
                    <button type="submit" class="workspace-btn-primary w-full justify-center">Confirm provider switch</button>
                </form>
            </aside>
        </div>
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
