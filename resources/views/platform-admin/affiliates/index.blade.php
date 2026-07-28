@extends('platform-admin.layouts.app')

@section('title', 'Affiliates')
@section('eyebrow', 'Network')
@section('heading', 'Affiliates')
@section('content')
<div class="mb-5 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
    <div>
        <h2 class="text-xl font-bold tracking-tight">Every affiliate, one view</h2>
        <p class="mt-1 text-sm text-slate-500">Open a tenant to manage its users, activity and bank configuration.</p>
    </div>
    <form class="flex gap-2">
        <input name="search" value="{{ $search }}" placeholder="Search affiliates…" class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500 sm:w-72">
        <button class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white">Search</button>
    </form>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
@forelse($affiliates as $affiliate)
    <a href="{{ route('platform-admin.affiliates.show', $affiliate) }}" class="group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-emerald-300 hover:shadow-md">
        <div class="p-4">
            <div class="flex items-center gap-3">
                <div class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-slate-100 text-sm font-bold text-slate-700 group-hover:bg-emerald-50 group-hover:text-emerald-700">{{ strtoupper(substr($affiliate->name, 0, 1)) }}</div>
                <div class="min-w-0 flex-1">
                    <h3 class="truncate font-semibold text-slate-900">{{ $affiliate->name }}</h3>
                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $affiliate->contact_email }}</p>
                </div>
                <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $affiliate->activation_status == 1 ? 'bg-emerald-500' : 'bg-amber-400' }}" title="{{ $affiliate->activation_status == 1 ? 'Active' : 'Inactive' }}"></span>
            </div>
        </div>
        <div class="flex items-center divide-x divide-slate-200 border-t border-slate-100 bg-slate-50/70 px-4 py-2.5 text-xs">
            <div class="flex flex-1 items-center gap-1.5 pr-3"><strong class="text-sm text-slate-800">{{ number_format($affiliate->users_count) }}</strong><span class="text-slate-500">users</span></div>
            <div class="flex flex-1 items-center gap-1.5 pl-3"><strong class="text-sm text-slate-800">{{ number_format($affiliate->transactions_count) }}</strong><span class="truncate text-slate-500">transactions</span></div>
        </div>
    </a>
@empty
    <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center text-slate-500">No affiliates matched your search.</div>
@endforelse
</div>
<div class="mt-5">{{ $affiliates->links() }}</div>
@endsection
