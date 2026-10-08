@extends('parent-admin.layouts.app')

@section('title', 'Preview pasted price update')
@section('heading', 'Preview pasted price update')

@section('content')
<div class="space-y-5">
    <section class="rounded-2xl border bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-amber-600">Review before saving</p>
                <h2 class="mt-1 text-lg font-semibold">{{ $category->product_plan_category_name }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $category->product?->product_name ?: 'No product' }} · {{ $category->network?->network_name ?: 'No network' }}</p>
            </div>
            <a href="{{ route('parent-admin.product-plans.index') }}" class="rounded-xl border px-4 py-2 text-sm font-semibold">Back to product plans</a>
        </div>
    </section>

    @if($errors)
        <section class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
            <h2 class="font-semibold">Fix these issues and preview again</h2>
            <ul class="mt-3 list-disc space-y-1 pl-5">
                @foreach($errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="overflow-hidden rounded-2xl border bg-white shadow-sm">
        <div class="border-b p-5">
            <h2 class="font-semibold">Rows found</h2>
            <p class="mt-1 text-sm text-slate-500">{{ count($rows) }} rows parsed. Green rows update existing plans; blue rows create new plans.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="p-3">Action</th>
                        <th class="p-3">Line</th>
                        <th class="p-3">Plan name</th>
                        <th class="p-3">api_id</th>
                        <th class="p-3">Cost/Admin cost</th>
                        <th class="p-3">Selling price</th>
                        <th class="p-3">Margin</th>
                        <th class="p-3">Current plan</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($rows as $row)
                        <tr>
                            <td class="p-3"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $row['classification'] === 'update' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">{{ $row['classification'] }}</span></td>
                            <td class="p-3">{{ $row['line'] }}</td>
                            <td class="p-3 font-medium">{{ $row['product_plan_name'] }}</td>
                            <td class="p-3 font-mono text-xs">{{ $row['api_id'] }}</td>
                            <td class="p-3">₦{{ number_format((float) $row['cost_price'], 2) }}</td>
                            <td class="p-3">₦{{ number_format((float) $row['selling_price'], 2) }}</td>
                            <td class="p-3">₦{{ number_format((float) $row['margin'], 2) }}</td>
                            <td class="p-3 text-xs text-slate-500">
                                @if($row['existing'] ?? null)
                                    {{ $row['existing']['product_plan_name'] }} · ₦{{ number_format((float) $row['existing']['cost_price'], 2) }}
                                @else
                                    New parent plan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-8 text-center text-slate-400">No rows parsed.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t bg-slate-50 p-5">
            <p class="text-xs text-slate-500">Nothing has been saved yet.</p>
            @if($token)
                <form method="POST" action="{{ route('parent-admin.product-plans.paste-prices.confirm') }}" onsubmit="return confirm('Apply these pasted price changes now?')">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <button class="rounded-xl bg-amber-600 px-5 py-3 text-sm font-semibold text-white">Confirm and save changes</button>
                </form>
            @endif
        </div>
    </section>
</div>
@endsection
