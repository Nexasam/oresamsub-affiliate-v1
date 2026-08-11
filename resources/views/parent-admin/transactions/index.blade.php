@extends('parent-admin.layouts.app')
@section('title', 'Transactions')
@section('heading', 'Transactions & reconciliation')
@section('content')
<div class="space-y-5">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([['Transactions', number_format($summary['count'])], ['Volume', '₦'.number_format($summary['volume'], 2)], ['Successful', number_format($summary['successful'])], ['Needs reconciliation', number_format($summary['reconciliation'])]] as [$label, $value])
        <div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-bold">{{ $value }}</p></div>
        @endforeach
    </div>
    <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-5">
        <input name="reference" value="{{ request('reference') }}" placeholder="Reference" class="rounded-xl border-slate-300 text-sm">
        <select name="affiliate_id" class="rounded-xl border-slate-300 text-sm"><option value="">All affiliates</option>@foreach($affiliates as $affiliate)<option value="{{ $affiliate->id }}" @selected((string) request('affiliate_id') === (string) $affiliate->id)>{{ $affiliate->name }}</option>@endforeach</select>
        <select name="service" class="rounded-xl border-slate-300 text-sm"><option value="">All services</option>@foreach(['data','airtime','cable_subscription','utility_bills'] as $service)<option value="{{ $service }}" @selected(request('service') === $service)>{{ str($service)->replace('_', ' ')->title() }}</option>@endforeach</select>
        <select name="routing_status" class="rounded-xl border-slate-300 text-sm"><option value="">All route states</option><option value="successful" @selected(request('routing_status') === 'successful')>Successful</option><option value="failed" @selected(request('routing_status') === 'failed')>Failed</option><option value="reconciliation_required" @selected(request('routing_status') === 'reconciliation_required')>Needs reconciliation</option></select>
        <button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Filter</button>
    </form>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Reference</th><th class="px-4 py-3">Affiliate</th><th class="px-4 py-3">Service</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Provider</th><th class="px-4 py-3">Date</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse($transactions as $transaction)<tr><td class="px-4 py-3 font-medium">{{ $transaction->txn_reference }}</td><td class="px-4 py-3">{{ $transaction->affiliate?->name ?? '—' }}</td><td class="px-4 py-3">{{ str($transaction->transaction_category)->replace('_', ' ')->title() }}</td><td class="px-4 py-3">₦{{ number_format($transaction->amount, 2) }}</td><td class="px-4 py-3">@if($transaction->routing_status === 'reconciliation_required')<span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">Needs reconciliation</span>@elseif((int) $transaction->status === 1)<span class="text-emerald-700">Successful</span>@elseif((int) $transaction->status === 2)<span class="text-rose-700">Failed / refunded</span>@else<span class="text-slate-600">Pending</span>@endif</td><td class="px-4 py-3">{{ $transaction->parentProviderConnection?->name ?? 'Legacy' }}</td><td class="px-4 py-3 text-slate-500">{{ $transaction->created_at }}</td></tr>@empty<tr><td colspan="7" class="px-4 py-12 text-center text-slate-500">No transactions match these filters.</td></tr>@endforelse
        </tbody></table></div><div class="border-t border-slate-200 p-4">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection
