@extends('parent-admin.layouts.app')
@section('title', 'Transactions')
@section('heading', 'Transactions & reconciliation')
@section('content')
<div class="space-y-5" x-data="{ reconciliationOpen: false, diagnosticsOpen: false, reconciliation: { action: '', outcome: '', reference: '', providerReference: '' }, diagnostics: {}, openReconciliation(event, outcome) { this.reconciliation = { action: event.currentTarget.dataset.action, outcome, reference: event.currentTarget.dataset.reference, providerReference: event.currentTarget.dataset.providerReference || '' }; this.reconciliationOpen = true }, closeReconciliation() { this.reconciliationOpen = false }, openDiagnostics(payload) { this.diagnostics = payload; this.diagnosticsOpen = true }, closeDiagnostics() { this.diagnosticsOpen = false } }" @keydown.escape.window="closeReconciliation(); closeDiagnostics()">
    <x-workspace.page-header title="Transactions & reconciliation" description="Track provider processing, investigate responses and complete pending cable or electricity requests." />
    @if(session('success'))<x-workspace.alert type="success">{{ session('success') }}</x-workspace.alert>@endif
    @if($errors->any())<x-workspace.alert type="error">{{ $errors->first() }}</x-workspace.alert>@endif
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach([['Transactions', number_format($summary['count'])], ['Volume', '₦'.number_format($summary['volume'], 2)], ['Successful', number_format($summary['successful'])], ['Needs reconciliation', number_format($summary['reconciliation'])], ['Manual review', number_format($summary['manual_review'])]] as [$label, $value])
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p><p class="mt-2 text-xl font-bold">{{ $value }}</p></div>
        @endforeach
    </div>
    <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-5">
        <input name="reference" value="{{ request('reference') }}" placeholder="Reference" class="rounded-xl border-slate-300 text-sm">
        <select name="affiliate_id" class="rounded-xl border-slate-300 text-sm"><option value="">All affiliates</option>@foreach($affiliates as $affiliate)<option value="{{ $affiliate->id }}" @selected((string) request('affiliate_id') === (string) $affiliate->id)>{{ $affiliate->name }}</option>@endforeach</select>
        <select name="service" class="rounded-xl border-slate-300 text-sm"><option value="">All services</option>@foreach(['data','airtime','cable_subscription','utility_bills'] as $service)<option value="{{ $service }}" @selected(request('service') === $service)>{{ str($service)->replace('_', ' ')->title() }}</option>@endforeach</select>
        <select name="routing_status" class="rounded-xl border-slate-300 text-sm"><option value="">All route states</option><option value="manual_pending" @selected(request('routing_status') === 'manual_pending')>Pending manual processing</option><option value="manual_successful" @selected(request('routing_status') === 'manual_successful')>Manually successful</option><option value="manual_failed" @selected(request('routing_status') === 'manual_failed')>Manually failed</option><option value="successful" @selected(request('routing_status') === 'successful')>Successful</option><option value="failed" @selected(request('routing_status') === 'failed')>Failed</option><option value="reconciliation_required" @selected(request('routing_status') === 'reconciliation_required')>Needs reconciliation</option><option value="reconciliation_exhausted" @selected(request('routing_status') === 'reconciliation_exhausted')>Manual review</option></select>
        <button class="workspace-btn-primary">Apply filters</button>
    </form>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="w-28 px-2 py-3">Ref</th><th class="px-3 py-3">Affiliate</th><th class="px-3 py-3">Service</th><th class="px-3 py-3">Amount</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Provider</th><th class="px-3 py-3">Provider response</th><th class="px-3 py-3">Date</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse($transactions as $transaction)
            @php
                $needsDiagnostics = (int) $transaction->status === 2
                    || in_array($transaction->routing_status, ['failed', 'reconciliation_required', 'reconciliation_exhausted'], true);
                $failureReason = filled($transaction->admin_screen_message)
                    ? $transaction->admin_screen_message
                    : (filled($transaction->user_screen_message) ? $transaction->user_screen_message : 'No failure reason was recorded.');
                $providerResponse = $transaction->provider_response;
                $planName = $transaction->product_plan?->product_plan?->product_plan_name
                    ?? $transaction->product_plan?->product_plan_name
                    ?? $transaction->api_id;
                $responsePreview = data_get($providerResponse, 'message')
                    ?? data_get($providerResponse, 'api_response')
                    ?? data_get($providerResponse, 'server_message')
                    ?? data_get($providerResponse, 'data.message')
                    ?? ($needsDiagnostics ? $failureReason : null);
                $diagnostics = [
                    'reference' => $transaction->txn_reference,
                    'affiliate' => $transaction->affiliate?->name ?? '—',
                    'service' => str($transaction->transaction_category)->replace('_', ' ')->title()->toString(),
                    'plan' => $planName ?: '—',
                    'amount' => '₦'.number_format($transaction->amount, 2),
                    'provider' => $transaction->parentProviderConnection?->name ?? 'Legacy',
                    'status' => str($transaction->routing_status ?: 'legacy')->replace('_', ' ')->title()->toString(),
                    'providerReference' => $transaction->provider_reference ?: '—',
                    'date' => $transaction->created_at,
                    'failureReason' => $needsDiagnostics ? $failureReason : null,
                    'response' => $providerResponse,
                ];
            @endphp
            <tr>
                <td class="w-28 px-2 py-3"><span class="block max-w-28 truncate font-mono text-[10px] font-medium text-slate-500" title="{{ $transaction->txn_reference }}">{{ str($transaction->txn_reference)->limit(18) }}</span></td>
                <td class="px-3 py-3">{{ $transaction->affiliate?->name ?? '—' }}</td>
                <td class="px-3 py-3">
                    <span class="block font-medium text-slate-800 dark:text-slate-100">{{ str($transaction->transaction_category)->replace('_', ' ')->title() }}</span>
                    @if(filled($planName))<span class="mt-0.5 block max-w-44 truncate text-[11px] text-slate-500" title="{{ $planName }}">{{ $planName }}</span>@endif
                </td>
                <td class="px-3 py-3">₦{{ number_format($transaction->amount, 2) }}</td>
                <td class="px-3 py-3">
                    @if($transaction->routing_status === 'manual_pending')
                        <div class="space-y-2"><span class="rounded-full bg-violet-100 px-2 py-1 text-xs font-semibold text-violet-800">Pending manual processing</span><div class="flex flex-wrap gap-1"><form method="POST" action="{{ route('parent-admin.transactions.manual-completion', $transaction->id) }}">@csrf @method('PATCH')<input type="hidden" name="outcome" value="successful"><button class="rounded-lg bg-emerald-600 px-2 py-1 text-xs font-semibold text-white" onclick="return confirm('Confirm this service was delivered successfully?')">Mark successful</button></form><form method="POST" action="{{ route('parent-admin.transactions.manual-completion', $transaction->id) }}">@csrf @method('PATCH')<input type="hidden" name="outcome" value="failed"><button class="rounded-lg bg-rose-600 px-2 py-1 text-xs font-semibold text-white" onclick="return confirm('Mark failed and refund the customer?')">Fail & refund</button></form></div></div>
                    @elseif(in_array($transaction->routing_status, ['reconciliation_required', 'reconciliation_exhausted'], true))
                        <div class="space-y-2">
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $transaction->routing_status === 'reconciliation_exhausted' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">{{ $transaction->routing_status === 'reconciliation_exhausted' ? 'Manual review' : 'Needs reconciliation' }}</span>
                            <div class="flex flex-wrap gap-1">
                                @foreach(['successful' => ['Confirm successful', 'bg-emerald-600'], 'failed' => ['Fail & refund', 'bg-rose-600']] as $outcome => [$label, $color])
                                    <button type="button" data-action="{{ route('parent-admin.transactions.reconciliation.resolve', $transaction->id) }}" data-reference="{{ $transaction->txn_reference }}" data-provider-reference="{{ $transaction->provider_reference }}" @click="openReconciliation($event, '{{ $outcome }}')" class="rounded-lg {{ $color }} px-2 py-1 text-xs font-semibold text-white">{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>
                    @elseif((int) $transaction->status === 1)<span class="text-emerald-700">Successful</span>
                    @elseif((int) $transaction->status === 2)<span class="text-rose-700">Failed / refunded</span>
                    @else<span class="text-slate-600">Pending</span>@endif
                </td>
                <td class="px-3 py-3">{{ $transaction->parentProviderConnection?->name ?? 'Legacy' }}</td>
                <td class="max-w-sm px-3 py-3">
                    @if(filled($providerResponse) || $needsDiagnostics)
                        <button type="button" @click="openDiagnostics({{ Js::from($diagnostics) }})" class="group block w-full rounded-lg p-2 text-left transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:hover:bg-slate-800">
                            <span class="block max-w-72 truncate text-xs {{ $needsDiagnostics ? 'text-rose-700 dark:text-rose-300' : 'text-slate-700 dark:text-slate-200' }}" title="{{ is_scalar($responsePreview) ? $responsePreview : 'Provider response available' }}">{{ is_scalar($responsePreview) ? $responsePreview : 'Provider response available' }}</span>
                            <span class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-blue-700 dark:text-blue-300">View details <span aria-hidden="true">→</span></span>
                        </button>
                    @else
                        <span class="text-slate-400">—</span>
                    @endif
                </td>
                <td class="px-3 py-3 text-slate-500">{{ $transaction->created_at }}</td>
            </tr>
        @empty<tr><td colspan="8" class="px-4 py-12 text-center text-slate-500">No transactions match these filters.</td></tr>@endforelse
        </tbody></table></div><div class="border-t border-slate-200 p-4">{{ $transactions->links() }}</div>
    </div>

    <div x-cloak x-show="diagnosticsOpen" x-transition.opacity class="fixed inset-0 z-[80] bg-slate-950/60" @click="closeDiagnostics()"></div>
    <aside data-testid="transaction-diagnostics-drawer" x-cloak x-show="diagnosticsOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="fixed inset-y-0 right-0 z-[90] flex w-full max-w-xl flex-col bg-white shadow-2xl dark:bg-slate-900" role="dialog" aria-modal="true" aria-labelledby="transaction-diagnostics-title">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-5 dark:border-slate-700">
            <div class="min-w-0"><p class="text-xs font-bold uppercase tracking-wider text-blue-600">Provider diagnostics</p><h2 id="transaction-diagnostics-title" class="mt-1 truncate text-lg font-bold" x-text="diagnostics.plan || 'Transaction details'"></h2><p class="mt-1 truncate font-mono text-[11px] text-slate-500" x-text="diagnostics.reference"></p></div>
            <button type="button" @click="closeDiagnostics()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold dark:border-slate-600">Close</button>
        </div>
        <div class="flex-1 space-y-5 overflow-y-auto p-5">
            <dl class="grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-4 text-sm dark:bg-slate-800/70">
                <div><dt class="text-xs text-slate-500">Affiliate</dt><dd class="mt-1 font-semibold" x-text="diagnostics.affiliate"></dd></div>
                <div><dt class="text-xs text-slate-500">Service</dt><dd class="mt-1 font-semibold" x-text="diagnostics.service"></dd></div>
                <div><dt class="text-xs text-slate-500">Amount</dt><dd class="mt-1 font-semibold" x-text="diagnostics.amount"></dd></div>
                <div><dt class="text-xs text-slate-500">Route status</dt><dd class="mt-1 font-semibold" x-text="diagnostics.status"></dd></div>
                <div><dt class="text-xs text-slate-500">Provider</dt><dd class="mt-1 font-semibold" x-text="diagnostics.provider"></dd></div>
                <div><dt class="text-xs text-slate-500">Provider reference</dt><dd class="mt-1 break-all font-mono text-xs" x-text="diagnostics.providerReference"></dd></div>
                <div class="col-span-2"><dt class="text-xs text-slate-500">Date</dt><dd class="mt-1 font-semibold" x-text="diagnostics.date"></dd></div>
            </dl>
            <div x-show="diagnostics.failureReason" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-200"><p class="text-xs font-bold uppercase tracking-wide">Failure reason</p><p class="mt-2 whitespace-pre-wrap break-words" x-text="diagnostics.failureReason"></p></div>
            <section><h3 class="text-sm font-bold">Full redacted provider response</h3><pre class="mt-2 max-h-[28rem] overflow-auto whitespace-pre-wrap break-words rounded-xl bg-slate-950 p-4 font-mono text-xs leading-5 text-slate-100" x-text="diagnostics.response ? JSON.stringify(diagnostics.response, null, 2) : 'No provider response was stored.'"></pre></section>
        </div>
    </aside>

    <div x-cloak x-show="reconciliationOpen" x-transition.opacity class="fixed inset-0 z-[80] bg-slate-950/60" @click="closeReconciliation()"></div>
    <div data-testid="reconciliation-modal" x-cloak x-show="reconciliationOpen" class="fixed inset-0 z-[90] grid place-items-center overflow-y-auto p-4" role="dialog" aria-modal="true" aria-labelledby="reconciliation-title">
        <form method="POST" :action="reconciliation.action" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900" @click.stop>
            @csrf @method('PATCH')
            <input type="hidden" name="outcome" :value="reconciliation.outcome">
            <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-wider text-blue-600">Manual reconciliation</p><h2 id="reconciliation-title" class="mt-1 text-xl font-bold" x-text="reconciliation.outcome === 'successful' ? 'Confirm successful transaction' : 'Fail and refund transaction'"></h2></div><button type="button" @click="closeReconciliation()" class="rounded-lg border px-3 py-2 text-sm">Close</button></div>
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"><strong x-text="reconciliation.reference"></strong><p class="mt-1" x-text="reconciliation.outcome === 'successful' ? 'This captures the reserved settlement amount. The customer remains charged.' : 'This releases the settlement reservation and refunds the customer exactly once.'"></p></div>
            <div class="mt-5 space-y-4">
                <label class="block text-sm font-medium">Provider reference (optional)<input name="provider_reference" x-model="reconciliation.providerReference" maxlength="255" class="mt-1 w-full rounded-xl border-slate-300"></label>
                <label class="block text-sm font-medium">Reconciliation note<textarea name="note" required minlength="10" maxlength="1000" rows="3" placeholder="Explain where and how you confirmed the provider outcome" class="mt-1 w-full rounded-xl border-slate-300"></textarea></label>
                <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm"><input type="checkbox" name="provider_confirmed" value="1" required class="mt-0.5 rounded border-slate-300"><span>I checked the provider dashboard or transaction-status endpoint and confirm this outcome is correct.</span></label>
            </div>
            <div class="mt-6 flex justify-end gap-3"><button type="button" @click="closeReconciliation()" class="rounded-xl border px-4 py-2.5 font-semibold">Cancel</button><button class="rounded-xl px-5 py-2.5 font-semibold text-white" :class="reconciliation.outcome === 'successful' ? 'bg-emerald-600' : 'bg-rose-600'" x-text="reconciliation.outcome === 'successful' ? 'Confirm successful' : 'Fail & refund'"></button></div>
        </form>
    </div>
</div>
@endsection
