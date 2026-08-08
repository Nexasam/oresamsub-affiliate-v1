@extends('parent-admin.layouts.app')

@section('title', 'Product plans')
@section('heading', 'Product plans')

@section('content')
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Workspace ready</p>
    <h2 class="mt-2 text-2xl font-bold">Product plans and Pricing</h2>
    <p class="mt-3 max-w-2xl text-slate-600">This secure workspace is connected to {{ auth('parent_admin')->user()->parentBusiness->name }}. Product-plan management and the six-level pricing controls will be added here in the next batches.</p>
</section>
@endsection
