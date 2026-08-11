@extends('platform-admin.layouts.app')
@section('title', 'Provider routing rollout')
@section('heading', 'Provider routing rollout')
@section('content')
<div class="space-y-5">
    <div class="rounded-2xl border p-4 {{ $environmentEnabled ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-900' }}">
        <p class="font-semibold">Environment kill switch: {{ $environmentEnabled ? 'ON' : 'OFF' }}</p>
        <p class="mt-1 text-sm">Rules below execute only when <code>PARENT_PROVIDER_ROUTING=true</code>. Affiliate rules override their parent service rule.</p>
    </div>
    @if(session('success'))<div class="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @foreach($parents as $parent)
    <section class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4"><h2 class="font-semibold">{{ $parent->name }}</h2><p class="text-sm text-slate-500">Enable a service for the parent, then optionally disable or enable it for a specific affiliate.</p></div>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach($services as $service)
            @php($rule = $rollouts->get("parent:{$parent->id}:{$service}"))
            <form method="POST" action="{{ route('platform-admin.provider-routing-rollouts.update') }}" class="rounded-xl border border-slate-200 p-3">@csrf @method('PUT')
                <input type="hidden" name="parent_business_id" value="{{ $parent->id }}"><input type="hidden" name="scope_type" value="parent"><input type="hidden" name="scope_id" value="{{ $parent->id }}"><input type="hidden" name="service" value="{{ $service }}"><input type="hidden" name="enabled" value="{{ $rule?->enabled ? 0 : 1 }}">
                <p class="text-sm font-semibold">{{ str($service)->replace('_', ' ')->title() }}</p><button class="mt-2 rounded-lg px-3 py-1.5 text-xs font-semibold {{ $rule?->enabled ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ $rule?->enabled ? 'Enabled · disable' : 'Disabled · enable' }}</button>
            </form>
            @endforeach
        </div>
        @if($parent->affiliates->isNotEmpty())
        <form method="POST" action="{{ route('platform-admin.provider-routing-rollouts.update') }}" class="mt-4 grid gap-3 border-t border-slate-100 pt-4 md:grid-cols-4">@csrf @method('PUT')
            <input type="hidden" name="parent_business_id" value="{{ $parent->id }}"><input type="hidden" name="scope_type" value="affiliate">
            <select name="scope_id" required class="rounded-xl border-slate-300 text-sm"><option value="">Affiliate override…</option>@foreach($parent->affiliates as $affiliate)<option value="{{ $affiliate->id }}">{{ $affiliate->name }}</option>@endforeach</select>
            <select name="service" class="rounded-xl border-slate-300 text-sm">@foreach($services as $service)<option value="{{ $service }}">{{ str($service)->replace('_', ' ')->title() }}</option>@endforeach</select>
            <select name="enabled" class="rounded-xl border-slate-300 text-sm"><option value="1">Enable</option><option value="0">Disable</option></select>
            <button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Save affiliate override</button>
        </form>
        @endif
    </section>
    @endforeach
</div>
@endsection
