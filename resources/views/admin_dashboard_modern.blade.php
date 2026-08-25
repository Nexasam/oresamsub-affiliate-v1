@extends('layouts.app')

@section('content')
<div class="main-content workspace-page">
    <div class="workspace-stack">
        <x-workspace.page-header title="Welcome, {{ $user->first_name }}" description="Monitor customers, transactions, product plans and business funding from one place.">
            <a href="{{ route('admin.transactions.index') }}" class="workspace-btn-secondary">View transactions</a>
            <a href="{{ route('admin.product_plans.index') }}" class="workspace-btn-primary">Manage plans</a>
        </x-workspace.page-header>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach([
                ['Customers', count($users ?? []), 'Registered accounts'],
                ['Transactions', count($transactions ?? []), 'All recorded purchases'],
                ['Product plans', count($product_plans ?? []), 'Available catalogue'],
                ['Admin wallet', '₦'.number_format((float) auth()->user()->main_wallet, 2), 'Operating balance'],
                ['Customer balances', '₦'.number_format((float) ($main_wallet_balances ?? 0), 2), 'Combined balances'],
            ] as [$label, $value, $description])
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $label }}</p>
                    <p class="mt-2 truncate text-xl font-bold text-slate-950 dark:text-white">{{ $value }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $description }}</p>
                </div>
            @endforeach
        </section>

        @if(config('parent_businesses.features.multi_parent_funding') && session('affiliate')?->parent_business_id)
            <section class="workspace-panel">
                <div class="workspace-panel-header">
                    <div><h2 class="font-semibold">Settlement wallet</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Business funds reserved for parent-managed purchases.</p></div>
                    <a href="{{ route('admin.settlement-funding.index') }}" class="workspace-btn-secondary">Manage funding</a>
                </div>
                <div class="workspace-panel-body grid gap-4 lg:grid-cols-[220px_1fr]">
                    <div
                        class="rounded-xl bg-slate-950 p-4 text-white dark:bg-slate-800"
                        x-data="{
                            available: @js((string) ($settlement_wallet?->available_balance ?? '0.00')),
                            reserved: @js((string) ($settlement_wallet?->reserved_balance ?? '0.00')),
                            refreshing: false,
                            format(value) { return Number(value || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                            async refreshBalance() {
                                if (this.refreshing) return;
                                this.refreshing = true;
                                try {
                                    const response = await fetch(@js(route('admin.settlement-wallet.refresh')), {
                                        method: 'POST',
                                        credentials: 'same-origin',
                                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                                    });
                                    if (!response.ok) throw new Error('Unable to refresh balance');
                                    const balance = await response.json();
                                    this.available = balance.available_balance;
                                    this.reserved = balance.reserved_balance;
                                } finally { this.refreshing = false; }
                            }
                        }"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Available</p>
                            <button type="button" @click="refreshBalance" :disabled="refreshing" class="grid h-8 w-8 place-items-center rounded-lg bg-white/10 text-slate-300 transition hover:bg-white/20 hover:text-white disabled:opacity-50" title="Refresh settlement balance" aria-label="Refresh settlement balance">
                                <svg class="h-4 w-4" :class="refreshing ? 'animate-spin' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5M4 13a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5"/></svg>
                            </button>
                        </div>
                        <p class="mt-2 text-2xl font-bold">₦<span x-text="format(available)">{{ number_format((float) ($settlement_wallet?->available_balance ?? 0), 2) }}</span></p>
                        <p class="mt-2 text-xs text-slate-400">Reserved ₦<span x-text="format(reserved)">{{ number_format((float) ($settlement_wallet?->reserved_balance ?? 0), 2) }}</span></p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @forelse($settlement_accounts as $account)
                            <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700"><p class="text-xs font-semibold uppercase text-slate-500">{{ $account->bank_name }}</p><p class="mt-1 text-lg font-bold tracking-wide">{{ $account->account_number }}</p><p class="text-xs text-slate-500">{{ $account->account_name }}</p></div>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500 dark:border-slate-700">No settlement account generated yet.</div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="workspace-panel">
                <div class="workspace-panel-header"><div><h2 class="font-semibold">Recent settlement funding</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Latest verified credits into your business settlement wallet.</p></div><a href="{{ route('admin.settlement-funding.index') }}" class="workspace-btn-secondary">View all</a></div>
                <div class="workspace-table-wrap">
                    <table class="workspace-table min-w-[760px]">
                        <thead><tr><th>Date</th><th>Reference</th><th>Gross</th><th>Charge</th><th>Net credit</th><th>Balance</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse($settlement_funding_entries as $entry)
                            <tr>
                                <td class="whitespace-nowrap text-xs text-slate-500"><x-workspace.date :value="$entry->created_at" /></td>
                                <td class="font-medium">{{ data_get($entry->metadata, 'external_event_id', str($entry->reference)->after('FUNDING:')) }}</td>
                                <td>₦{{ number_format((float) data_get($entry->metadata, 'gross_amount', $entry->amount), 2) }}</td>
                                <td>₦{{ number_format((float) data_get($entry->metadata, 'charge', 0), 2) }}</td>
                                <td class="font-semibold text-emerald-700 dark:text-emerald-400">+₦{{ number_format((float) $entry->amount, 2) }}</td>
                                <td>₦{{ number_format((float) $entry->balance_after, 2) }}</td>
                                <td><x-workspace.status type="success">Successful</x-workspace.status></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="workspace-empty">No automated settlement funding yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="workspace-panel">
            <div class="workspace-panel-header"><div><h2 class="font-semibold">Customer funding accounts</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Virtual accounts used by customers to fund their wallets.</p></div><a href="{{ route('admin.affiliate-funding-providers.index') }}" class="workspace-btn-secondary">Funding settings</a></div>
            <div class="workspace-panel-body grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @forelse($user_virtual_accounts as $account)
                    @php($charge = $account->fundingChargeDetails())
                    <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700"><p class="text-xs font-semibold uppercase text-slate-500">{{ $account->bank_name }}</p><p class="mt-1 text-lg font-bold tracking-wide">{{ $account->account_number }}</p><p class="text-sm text-slate-500">{{ $account->account_name }}</p><p class="mt-2 text-xs text-slate-400">Charge: {{ $charge['display'] ?? 'Provider configured' }}</p></div>
                @empty
                    <div class="sm:col-span-2 xl:col-span-4"><p class="workspace-empty">No customer funding account has been generated.</p></div>
                @endforelse
            </div>
        </section>

        <section class="workspace-panel">
            <div class="workspace-panel-header"><div><h2 class="font-semibold">Recent transactions</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Latest customer purchase activity.</p></div><a href="{{ route('admin.transactions.index') }}" class="workspace-btn-secondary">View all</a></div>
            <div class="workspace-table-wrap">
                <table class="workspace-table min-w-[760px]">
                    <thead><tr><th>Reference</th><th>Customer</th><th>Service</th><th>Amount</th><th>Status</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                    @forelse(collect($transactions)->take(10) as $transaction)
                        <tr>
                            <td><span class="block max-w-44 truncate font-mono text-xs" title="{{ $transaction->txn_reference }}">{{ $transaction->txn_reference ?: '#'.$transaction->id }}</span></td>
                            <td>{{ $transaction->user?->first_name }} {{ $transaction->user?->last_name }}</td>
                            <td>{{ str($transaction->transaction_category)->replace('_', ' ')->title() }}</td>
                            <td class="font-semibold">₦{{ number_format((float) $transaction->amount, 2) }}</td>
                            <td>
                                @if((int)$transaction->status === 1)<x-workspace.status type="success">Successful</x-workspace.status>
                                @elseif((int)$transaction->status === 2)<x-workspace.status type="failed">Refunded</x-workspace.status>
                                @elseif((int)$transaction->status === 0)<x-workspace.status type="pending">Pending</x-workspace.status>
                                @else<x-workspace.status>{{ str($transaction->routing_status ?: 'processing')->headline() }}</x-workspace.status>@endif
                            </td>
                            <td class="whitespace-nowrap text-xs text-slate-500"><x-workspace.date :value="$transaction->created_at" /></td>
                            <td><a href="{{ route('transactions.transaction_details', $transaction->id) }}" class="workspace-btn-secondary">Details</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="workspace-empty">No transactions yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if(config('parent_businesses.features.multi_parent_funding') && session('affiliate')?->parent_business_id)
        <section class="workspace-panel">
            <div class="workspace-panel-header"><div><h2 class="font-semibold">Settlement wallet activity</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Why your business settlement balance was debited or credited.</p></div><a href="{{ route('admin.settlement-wallet.activity') }}" class="workspace-btn-secondary">View full ledger</a></div>
            <div class="workspace-table-wrap">
                <table class="workspace-table min-w-[920px]">
                    <thead><tr><th>Date</th><th>Activity</th><th>Reference</th><th>Service</th><th>Method</th><th>Reason</th><th>Movement</th><th>Balance</th></tr></thead>
                    <tbody>
                    @forelse($settlement_activity_entries as $entry)
                        <tr>
                            <td class="whitespace-nowrap text-xs text-slate-500"><x-workspace.date :value="$entry->created_at" /></td>
                            <td class="font-semibold">{{ $entry->displayLabel() }}</td>
                            <td><span class="block max-w-48 truncate font-mono text-xs" title="{{ $entry->purchaseReference() }}">{{ $entry->purchaseReference() }}</span></td>
                            <td>{{ $entry->displayService() ?: '—' }}</td>
                            <td>{{ $entry->displayMethod() }}</td>
                            <td><span class="block max-w-52 truncate" title="{{ $entry->reason }}">{{ $entry->reason }}</span></td>
                            <td class="whitespace-nowrap font-bold {{ $entry->displayColor() }} dark:brightness-125">{{ $entry->displaySign() }}₦{{ number_format((float) $entry->amount, 2) }}</td>
                            <td class="whitespace-nowrap font-semibold">₦{{ number_format((float) $entry->balance_after, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="workspace-empty">No settlement wallet activity yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @endif
    </div>
</div>
@endsection
