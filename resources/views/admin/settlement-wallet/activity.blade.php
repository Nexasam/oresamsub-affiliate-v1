@extends('layouts.app')

@section('content')
<div class="main-content workspace-page">
    <div class="workspace-stack">
        <x-workspace.page-header title="Settlement wallet activity" description="A complete audit trail of every debit, credit, reservation, release and refund on your business settlement wallet.">
            <a href="{{ route('dashboard') }}" class="workspace-btn-secondary">Back to dashboard</a>
            <a href="{{ route('admin.settlement-funding.index') }}" class="workspace-btn-primary">Fund settlement wallet</a>
        </x-workspace.page-header>

        <section class="workspace-panel">
            <form method="GET" class="workspace-panel-body grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                <label class="xl:col-span-2"><span class="mb-1 block text-xs font-semibold text-slate-500">Reference or reason</span><input name="search" value="{{ $filters['search'] ?? '' }}" class="workspace-input w-full" placeholder="Search activity"></label>
                <label><span class="mb-1 block text-xs font-semibold text-slate-500">Activity</span><select name="type" class="workspace-input w-full"><option value="">All activities</option>@foreach(['purchase_reservation' => 'Purchase reservation','purchase_capture' => 'Purchase capture','reservation_release' => 'Reservation release','refund' => 'Refund','settlement_funding' => 'Settlement funding','manual_credit' => 'Manual credit'] as $value => $label)<option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="mb-1 block text-xs font-semibold text-slate-500">Movement</span><select name="direction" class="workspace-input w-full"><option value="">Credits and debits</option><option value="debit" @selected(($filters['direction'] ?? '') === 'debit')>Debits</option><option value="credit" @selected(($filters['direction'] ?? '') === 'credit')>Credits</option></select></label>
                <label><span class="mb-1 block text-xs font-semibold text-slate-500">From</span><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="workspace-input w-full"></label>
                <label><span class="mb-1 block text-xs font-semibold text-slate-500">To</span><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="workspace-input w-full"></label>
                <div class="flex gap-2 sm:col-span-2 xl:col-span-6"><button class="workspace-btn-primary">Apply filters</button><a href="{{ route('admin.settlement-wallet.activity') }}" class="workspace-btn-secondary">Clear</a></div>
            </form>
        </section>

        <section class="workspace-panel">
            <div class="workspace-table-wrap">
                <table class="workspace-table min-w-[1050px]">
                    <thead><tr><th>Date</th><th>Activity</th><th>Reference</th><th>Service</th><th>Method</th><th>Reason</th><th>Amount</th><th>Balance after</th></tr></thead>
                    <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td class="whitespace-nowrap text-xs text-slate-500"><x-workspace.date :value="$entry->created_at" /></td>
                            <td class="font-semibold">{{ $entry->displayLabel() }}</td>
                            <td><span class="block max-w-56 truncate font-mono text-xs" title="{{ $entry->purchaseReference() }}">{{ $entry->purchaseReference() }}</span></td>
                            <td>{{ $entry->displayService() ?: '—' }}</td>
                            <td>{{ $entry->displayMethod() }}</td>
                            <td><span class="block max-w-64 truncate" title="{{ $entry->reason }}">{{ $entry->reason }}</span></td>
                            <td class="whitespace-nowrap font-bold {{ $entry->displayColor() }} dark:brightness-125">{{ $entry->displaySign() }}₦{{ number_format((float) $entry->amount, 2) }}</td>
                            <td class="whitespace-nowrap font-semibold">₦{{ number_format((float) $entry->balance_after, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="workspace-empty">No settlement wallet activity matches these filters.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($entries->hasPages())<div class="border-t border-slate-200 px-4 py-4 dark:border-slate-700">{{ $entries->links() }}</div>@endif
        </section>
    </div>
</div>
@endsection
