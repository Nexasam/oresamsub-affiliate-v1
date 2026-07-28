@extends('platform-admin.layouts.app')

@section('title', 'Overview')
@section('heading', 'Overview')
@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    @foreach([
        ['Affiliates', number_format($stats['affiliates']), $stats['activeAffiliates'].' active'],
        ['Users', number_format($stats['users']), 'Across every tenant'],
        ['Transactions', number_format($stats['transactions']), 'All time'],
        ['Successful volume', '₦'.number_format($stats['volume'], 2), 'Completed transactions'],
    ] as [$label, $value, $note])
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:first:col-span-1">
            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-3 text-2xl font-bold tracking-tight">{{ $value }}</p>
            <p class="mt-2 text-xs text-slate-400">{{ $note }}</p>
        </article>
    @endforeach
    <article class="rounded-2xl bg-slate-950 p-5 text-white shadow-sm">
        <p class="text-sm font-medium text-slate-400">System coverage</p>
        <p class="mt-3 text-2xl font-bold">{{ $stats['affiliates'] ? round(($stats['activeAffiliates'] / $stats['affiliates']) * 100) : 0 }}%</p>
        <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10">
            <div class="h-full rounded-full bg-emerald-400" style="width: {{ $stats['affiliates'] ? ($stats['activeAffiliates'] / $stats['affiliates']) * 100 : 0 }}%"></div>
        </div>
    </article>
</div>

<section class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
        <div>
            <h2 class="font-semibold">Recently added affiliates</h2>
            <p class="mt-1 text-sm text-slate-500">Quick access to the newest systems.</p>
        </div>
        <a href="{{ route('platform-admin.affiliates.index') }}" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">View all →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-6 py-4">Affiliate</th><th class="px-6 py-4">Users</th><th class="px-6 py-4">Transactions</th><th class="px-6 py-4">Status</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($affiliates as $affiliate)
                <tr class="hover:bg-slate-50/80">
                    <td class="px-6 py-4"><p class="font-semibold">{{ $affiliate->name }}</p><p class="text-xs text-slate-400">{{ $affiliate->contact_email }}</p></td>
                    <td class="px-6 py-4">{{ number_format($affiliate->users_count) }}</td>
                    <td class="px-6 py-4">{{ number_format($affiliate->transactions_count) }}</td>
                    <td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $affiliate->activation_status == 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $affiliate->activation_status == 1 ? 'Active' : 'Inactive' }}</span></td>
                    <td class="px-6 py-4 text-right"><a href="{{ route('platform-admin.affiliates.show', $affiliate) }}" class="font-semibold text-slate-700 hover:text-emerald-600">Manage</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">No affiliates are available yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
