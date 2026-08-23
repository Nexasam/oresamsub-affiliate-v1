@extends('parent-admin.layouts.app')

@section('heading', 'Preview plan import')

@section('content')
<div class="space-y-5">
    @php($newCount = collect($rows)->where('classification', 'new')->count())
    @php($updateCount = collect($rows)->where('classification', 'update')->count())
    @php($invalidCount = count($errors ?? []))
    <section class="rounded-2xl border bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Review before import</p>
                <h2 class="mt-1 text-xl font-bold">{{ count($rows) }} valid plans ready</h2>
                <p class="mt-1 text-sm text-slate-500">Nothing has been saved. Existing matches use the provider connection and external plan ID.</p>
            </div>
            <div class="flex gap-2 text-sm font-semibold">
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-800">{{ $newCount }} new</span>
                <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-800">{{ $updateCount }} will update</span>
                @if($invalidCount)<span class="rounded-full bg-red-100 px-3 py-1 text-red-800">{{ $invalidCount }} invalid</span>@endif
            </div>
        </div>
    </section>

    @if($invalidCount)
        <section class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-900">
            <h3 class="font-bold">Correct these rows before importing</h3>
            <div class="mt-3 space-y-2 text-sm">@foreach($errors as $line => $error)<p><strong>Row {{ $line }}:</strong> {{ $error }}</p>@endforeach</div>
            <p class="mt-3 text-xs">Valid rows are shown below for reference, but confirmation is disabled until the entire workbook passes validation.</p>
        </section>
    @endif

    <section class="overflow-x-auto rounded-2xl border bg-white shadow-sm">
        <table class="w-full min-w-[1050px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-3">Row</th><th class="p-3">Action</th><th class="p-3">Plan</th><th class="p-3">Category</th><th class="p-3">Connection</th><th class="p-3">Provider ID</th><th class="p-3">Cost</th><th class="p-3">Six prices</th></tr></thead>
            <tbody class="divide-y">
            @foreach($rows as $row)
                @php($plan = $row['attributes'])
                <tr>
                    <td class="p-3 text-slate-500">{{ $row['line'] }}</td>
                    <td class="p-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $row['classification']==='new' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ $row['classification']==='new' ? 'New plan' : 'Will update' }}</span></td>
                    <td class="p-3 font-semibold">{{ $plan['product_plan_name'] }}</td>
                    <td class="p-3">{{ $row['category_label'] }}</td>
                    <td class="p-3">{{ $row['connection_label'] }}</td>
                    <td class="p-3 font-mono text-xs">{{ $plan['route']['provider_plan_id'] }}</td>
                    <td class="p-3">₦{{ number_format($plan['cost_price'], 2) }}</td>
                    <td class="p-3 text-xs">{{ collect($plan['prices'])->pluck('selling_price')->map(fn($price) => '₦'.number_format($price, 2))->implode(' · ') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>

    <div class="flex flex-wrap justify-between gap-3">
        <a href="{{ route('parent-admin.product-plans.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">Cancel and revise workbook</a>
        @if($token)<form method="POST" action="{{ route('parent-admin.product-plans.import.confirm') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <button class="rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white shadow-sm">Confirm {{ $newCount ? 'and import' : 'updates' }}</button>
        </form>@else<span class="rounded-xl bg-slate-200 px-5 py-3 font-semibold text-slate-500">Fix invalid rows to continue</span>@endif
    </div>
</div>
@endsection
