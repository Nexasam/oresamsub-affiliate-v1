@extends('layouts.app')

@section('content')
<div class="main-content workspace-page" x-data="{ transactionDrawerOpen: false, transactionDetails: {}, openTransactionDrawer(details) { this.transactionDetails = details; this.transactionDrawerOpen = true }, closeTransactionDrawer() { this.transactionDrawerOpen = false } }" @keydown.escape.window="closeTransactionDrawer()">
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
                    <thead><tr><th>Reference</th><th>Customer</th><th>Service</th><th>Amount</th><th>Status</th><th>Date</th><th>Provider response</th></tr></thead>
                    <tbody>
                    @if($transactions->isEmpty())
                        <tr><td colspan="7" class="workspace-empty">No transactions yet.</td></tr>
                    @else
                    @foreach($transactions as $transaction)
                        <?php
                            $planName = $transaction->product_plan?->product_plan?->product_plan_name
                                ?? $transaction->product_plan?->product_plan_name
                                ?? $transaction->api_id;
                            $providerResponse = $transaction->provider_response;
                            $responsePreview = data_get($providerResponse, 'message')
                                ?? data_get($providerResponse, 'api_response')
                                ?? data_get($providerResponse, 'server_message')
                                ?? data_get($providerResponse, 'data.message')
                                ?? $transaction->admin_screen_message
                                ?? $transaction->user_screen_message;
                            $statusLabel = (int) $transaction->status === 1
                                ? 'Successful'
                                : ((int) $transaction->status === 2
                                    ? 'Refunded'
                                    : ((int) $transaction->status === 0 ? 'Pending' : str($transaction->routing_status ?: 'processing')->headline()->toString()));
                            $drawerDetails = [
                                'reference' => $transaction->txn_reference ?: '#'.$transaction->id,
                                'customer' => trim(($transaction->user?->first_name ?? '').' '.($transaction->user?->last_name ?? '')) ?: '—',
                                'phone' => $transaction->phone_number ?: ($transaction->user?->phone_number ?: '—'),
                                'service' => str($transaction->transaction_category)->replace('_', ' ')->title()->toString(),
                                'plan' => $planName ?: '—',
                                'amount' => '₦'.number_format((float) $transaction->amount, 2),
                                'status' => $statusLabel,
                                'routeStatus' => str($transaction->routing_status ?: 'legacy')->replace('_', ' ')->title()->toString(),
                                'provider' => $transaction->parentProviderConnection?->name ?? 'Legacy',
                                'providerReference' => $transaction->provider_reference ?: '—',
                                'date' => $transaction->created_at,
                                'failureReason' => (int) $transaction->status === 2 ? ($transaction->admin_screen_message ?: $transaction->user_screen_message) : null,
                                'response' => $providerResponse,
                            ];
                        ?>
                        <tr>
                            <td><span class="block max-w-44 truncate font-mono text-xs" title="{{ $transaction->txn_reference }}">{{ $transaction->txn_reference ?: '#'.$transaction->id }}</span></td>
                            <td>{{ $transaction->user?->first_name }} {{ $transaction->user?->last_name }}</td>
                            <td><span class="block">{{ str($transaction->transaction_category)->replace('_', ' ')->title() }}</span>@if(filled($planName))<span class="mt-0.5 block max-w-40 truncate text-[11px] text-slate-500" title="{{ $planName }}">{{ $planName }}</span>@endif</td>
                            <td class="font-semibold">₦{{ number_format((float) $transaction->amount, 2) }}</td>
                            <td>
                                @if((int)$transaction->status === 1)<x-workspace.status type="success">Successful</x-workspace.status>
                                @elseif((int)$transaction->status === 2)<x-workspace.status type="failed">Refunded</x-workspace.status>
                                @elseif((int)$transaction->status === 0)<x-workspace.status type="pending">Pending</x-workspace.status>
                                @else<x-workspace.status>{{ str($transaction->routing_status ?: 'processing')->headline() }}</x-workspace.status>@endif
                            </td>
                            <td class="whitespace-nowrap text-xs text-slate-500"><x-workspace.date :value="$transaction->created_at" /></td>
                            <td class="max-w-64">
                                @if(filled($responsePreview))<span class="mb-1 block max-w-56 truncate text-xs text-slate-600 dark:text-slate-300" title="{{ is_scalar($responsePreview) ? $responsePreview : 'Provider response available' }}">{{ is_scalar($responsePreview) ? $responsePreview : 'Provider response available' }}</span>@endif
                                <button type="button" @click="openTransactionDrawer({{ Js::from($drawerDetails) }})" class="workspace-btn-secondary">Details</button>
                            </td>
                        </tr>
                    @endforeach
                    @endif
                    </tbody>
                </table>
            </div>
        </section>

        <div x-cloak x-show="transactionDrawerOpen" x-transition.opacity class="fixed inset-0 z-[80] bg-slate-950/60" @click="closeTransactionDrawer()"></div>
        <aside data-testid="affiliate-transaction-drawer" x-cloak x-show="transactionDrawerOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="fixed inset-y-0 right-0 z-[90] flex w-full max-w-xl flex-col bg-white shadow-2xl dark:bg-slate-900" role="dialog" aria-modal="true" aria-labelledby="affiliate-transaction-drawer-title">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-5 dark:border-slate-700">
                <div class="min-w-0"><p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Transaction details</p><h2 id="affiliate-transaction-drawer-title" class="mt-1 truncate text-lg font-bold" x-text="transactionDetails.plan || transactionDetails.service"></h2><p class="mt-1 truncate font-mono text-[11px] text-slate-500" x-text="transactionDetails.reference"></p></div>
                <button type="button" @click="closeTransactionDrawer()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold dark:border-slate-600">Close</button>
            </div>
            <div class="flex-1 space-y-5 overflow-y-auto p-5">
                <dl class="grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-4 text-sm dark:bg-slate-800/70">
                    <div><dt class="text-xs text-slate-500">Customer</dt><dd class="mt-1 font-semibold" x-text="transactionDetails.customer"></dd></div>
                    <div><dt class="text-xs text-slate-500">Phone number</dt><dd class="mt-1 font-semibold" x-text="transactionDetails.phone"></dd></div>
                    <div><dt class="text-xs text-slate-500">Service</dt><dd class="mt-1 font-semibold" x-text="transactionDetails.service"></dd></div>
                    <div><dt class="text-xs text-slate-500">Amount</dt><dd class="mt-1 font-semibold" x-text="transactionDetails.amount"></dd></div>
                    <div><dt class="text-xs text-slate-500">Status</dt><dd class="mt-1 font-semibold" x-text="transactionDetails.status"></dd></div>
                    <div><dt class="text-xs text-slate-500">Route status</dt><dd class="mt-1 font-semibold" x-text="transactionDetails.routeStatus"></dd></div>
                    <div><dt class="text-xs text-slate-500">Provider</dt><dd class="mt-1 font-semibold" x-text="transactionDetails.provider"></dd></div>
                    <div><dt class="text-xs text-slate-500">Provider reference</dt><dd class="mt-1 break-all font-mono text-xs" x-text="transactionDetails.providerReference"></dd></div>
                    <div class="col-span-2"><dt class="text-xs text-slate-500">Date</dt><dd class="mt-1 font-semibold" x-text="transactionDetails.date"></dd></div>
                </dl>
                <div x-show="transactionDetails.failureReason" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-200"><p class="text-xs font-bold uppercase tracking-wide">Failure reason</p><p class="mt-2 whitespace-pre-wrap break-words" x-text="transactionDetails.failureReason"></p></div>
                <section><h3 class="text-sm font-bold">Full redacted API response</h3><pre class="mt-2 max-h-[28rem] overflow-auto whitespace-pre-wrap break-words rounded-xl bg-slate-950 p-4 font-mono text-xs leading-5 text-slate-100" x-text="transactionDetails.response ? JSON.stringify(transactionDetails.response, null, 2) : 'No provider response was stored.'"></pre></section>
            </div>
        </aside>

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
