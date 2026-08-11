@extends('parent-admin.layouts.app')
@section('title','Affiliate operations')
@section('heading','Affiliate operations')
@section('content')
<div class="space-y-5" x-data="{open:false,search:'', selected:@js($selected?->only(['id','name','contact_email']))}">
    <section class="rounded-2xl border bg-white p-5 shadow-sm">
        <label class="text-sm font-semibold text-slate-700">Affiliate to manage</label>
        @if($selected)
        <div class="relative mt-2 max-w-2xl">
            <button type="button" @click="open=!open" class="flex w-full items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-left"><span><strong x-text="selected.name"></strong><small class="ml-2 text-slate-400" x-text="selected.contact_email"></small></span><span>⌄</span></button>
            <div x-cloak x-show="open" @click.outside="open=false" class="absolute z-20 mt-2 w-full rounded-xl border bg-white p-3 shadow-xl">
                <input x-model="search" x-ref="search" type="search" placeholder="Search affiliates by name or email…" class="w-full rounded-lg border-slate-200">
                <div class="mt-2 max-h-80 overflow-y-auto">
                    @foreach($affiliates as $affiliate)
                    <a x-show="@js(strtolower($affiliate->name.' '.$affiliate->contact_email)).includes(search.toLowerCase())" href="{{ route('parent-admin.operations.index',['affiliate_id'=>$affiliate->id]) }}" class="block rounded-lg px-3 py-3 hover:bg-slate-50"><strong>{{ $affiliate->name }}</strong><span class="ml-2 text-xs text-slate-400">{{ $affiliate->contact_email }}</span></a>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="mt-5 flex flex-wrap gap-2">
            <a href="{{ route('parent-admin.affiliates.edit',$selected) }}" class="rounded-xl border px-4 py-2 text-sm font-semibold">Affiliate details</a>
            <a href="{{ route('parent-admin.funding-providers.index') }}" class="rounded-xl border px-4 py-2 text-sm font-semibold">Funding</a>
            <a href="{{ route('parent-admin.pricing.affiliates.caps.show',$selected) }}" class="rounded-xl border px-4 py-2 text-sm font-semibold">Profit caps</a>
        </div>
        @else
        <p class="mt-3 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">No approved affiliates belong to this parent yet.</p>
        @endif
    </section>
</div>
@endsection
