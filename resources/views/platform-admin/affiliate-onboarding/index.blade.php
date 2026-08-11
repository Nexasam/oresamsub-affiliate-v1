@extends('platform-admin.layouts.app')
@section('title','Affiliate approvals')
@section('heading','Affiliate onboarding approvals')
@section('content')
<div class="space-y-4">
@if(session('success'))<div class="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
@forelse($requests as $item)
<article class="rounded-2xl border bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">{{ $item->parentBusiness->name }} · {{ $item->request_type }}</p><h2 class="mt-1 font-bold">{{ $item->affiliate?->name ?? $item->requested_name }}</h2><p class="text-sm text-slate-500">{{ $item->affiliate?->contact_email ?? $item->requested_email }} · {{ $item->resellerLevel->name }}</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold capitalize">{{ $item->status }}</span></div>
    @if($item->status==='pending')<div class="mt-4 flex flex-wrap gap-2"><form method="POST" action="{{ route('platform-admin.affiliate-onboarding.review',$item) }}">@csrf @method('PATCH')<button name="action" value="approve" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Approve</button></form><form method="POST" action="{{ route('platform-admin.affiliate-onboarding.review',$item) }}" class="flex gap-2">@csrf @method('PATCH')<input name="reason" required minlength="5" placeholder="Rejection reason" class="rounded-xl border-slate-200 text-sm"><button name="action" value="reject" class="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-700">Reject</button></form></div>@endif
</article>
@empty<div class="rounded-2xl border bg-white p-10 text-center text-slate-500">No affiliate onboarding requests.</div>@endforelse
<div>{{ $requests->links() }}</div>
</div>
@endsection
