@extends('parent-admin.layouts.app')

@section('title', 'Product plans')
@section('heading', 'Product plans')

@section('content')
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">{{ auth('parent_admin')->user()->parentBusiness->name }} workspace</p>
    <h2 class="mt-2 text-2xl font-bold">Manage your catalogue and reseller prices</h2>
    <p class="mt-3 max-w-2xl text-slate-600">Your product plans and normalized one-to-six-level pricing are isolated to this parent business.</p>
    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <a href="{{ route('parent-admin.product-plans.index') }}" class="rounded-2xl border border-slate-200 p-5 transition hover:border-blue-300 hover:bg-blue-50"><span class="text-sm font-semibold text-blue-600">CATALOGUE</span><h3 class="mt-2 text-lg font-bold">Open product plans</h3><p class="mt-1 text-sm text-slate-500">Create plans and manage categories, provider cost and availability.</p></a>
        <a href="{{ route('parent-admin.pricing.index') }}" class="rounded-2xl border border-slate-200 p-5 transition hover:border-blue-300 hover:bg-blue-50"><span class="text-sm font-semibold text-blue-600">RESELLER LEVELS</span><h3 class="mt-2 text-lg font-bold">Open pricing</h3><p class="mt-1 text-sm text-slate-500">Configure up to six levels and their plan prices.</p></a>
    </div>
</section>
@endsection
