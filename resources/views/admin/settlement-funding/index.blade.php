@extends('layouts.app')
@section('content')
<div class="main-content"><div class="mx-auto max-w-5xl space-y-5 p-4 sm:p-6">
    <div><h1 class="text-2xl font-bold text-slate-900">Settlement funding</h1><p class="mt-1 text-sm text-slate-600">Fund your business settlement wallet through virtual accounts issued with your parent’s approved funding providers.</p></div>
    @if(session('success'))<div class="rounded-xl bg-emerald-50 p-3 text-emerald-800">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-xl bg-rose-50 p-3 text-rose-800">{{ session('error') }}</div>@endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between"><div><h2 class="font-semibold text-slate-900">Business virtual accounts</h2><p class="text-sm text-slate-500">Transfers to these accounts credit only your affiliate settlement wallet.</p></div></div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @forelse($accounts as $account)<div class="rounded-xl border border-slate-200 p-4"><p class="text-xs font-semibold uppercase text-slate-500">{{ $account->bank_name }}</p><p class="mt-1 text-xl font-bold tracking-wide">{{ $account->account_number }}</p><p class="text-sm text-slate-600">{{ $account->account_name }}</p></div>@empty<p class="text-sm text-slate-500">No settlement virtual account has been generated yet.</p>@endforelse
        </div>
        <div class="mt-4 flex flex-wrap gap-2">@foreach($providers as $provider)<form method="POST" action="{{ route('admin.settlement-funding.generate',$provider) }}">@csrf<button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Generate with {{ $provider->fundingProvider->name }}</button></form>@endforeach</div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold text-slate-900">Settlement funding history</h2><div class="mt-3 divide-y divide-slate-100">@forelse($entries as $entry)<div class="flex items-center justify-between gap-3 py-3"><div><p class="text-sm font-medium">{{ $entry->reference }}</p><p class="text-xs text-slate-500">{{ $entry->created_at?->format('d M Y, H:i') }} · {{ str($entry->entry_type)->headline() }}</p></div><p class="font-semibold text-emerald-700">+₦{{ number_format((float)$entry->amount,2) }}</p></div>@empty<p class="py-3 text-sm text-slate-500">No settlement funding history yet.</p>@endforelse</div></section>
</div></div>
@endsection
