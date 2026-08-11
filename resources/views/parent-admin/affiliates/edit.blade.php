@extends('parent-admin.layouts.app')
@section('title','Edit affiliate')
@section('heading','Edit affiliate')
@section('content')
<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ route('parent-admin.affiliates.update', $affiliate) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div class="flex items-start justify-between gap-4">
            <div><h2 class="text-lg font-semibold">Edit affiliate</h2><p class="mt-1 text-sm text-slate-500">Update this affiliate's identity, contact details, domain and reseller level.</p></div>
            <a href="{{ route('parent-admin.affiliates.index') }}" class="rounded-lg border px-3 py-2 text-sm font-semibold">Back</a>
        </div>
        @if($errors->any())<div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <label class="grid gap-2 text-sm font-medium">Business name<input name="name" value="{{ old('name', $affiliate->name) }}" required class="rounded-xl border-slate-200"></label>
            <label class="grid gap-2 text-sm font-medium">Slug<input name="slug" value="{{ old('slug', $affiliate->slug) }}" required class="rounded-xl border-slate-200"></label>
            <label class="grid gap-2 text-sm font-medium">Email<input name="contact_email" value="{{ old('contact_email', $affiliate->contact_email) }}" type="email" required class="rounded-xl border-slate-200"></label>
            <label class="grid gap-2 text-sm font-medium">Phone<input name="contact_phone" value="{{ old('contact_phone', $affiliate->contact_phone) }}" required class="rounded-xl border-slate-200"></label>
            <label class="grid gap-2 text-sm font-medium">Domain<input name="domain_url" value="{{ old('domain_url', $affiliate->domain_url) }}" placeholder="https://affiliate.example" class="rounded-xl border-slate-200"></label>
            <label class="grid gap-2 text-sm font-medium">Reseller level<select name="parent_reseller_level_id" required class="rounded-xl border-slate-200"><option value="">Select level</option>@foreach($levels as $level)<option value="{{ $level->id }}" @selected((int) old('parent_reseller_level_id', $affiliate->parent_reseller_level_id) === $level->id)>{{ $level->name }}</option>@endforeach</select></label>
        </div>
        <div class="mt-6 flex justify-end"><button class="rounded-xl bg-blue-600 px-5 py-2.5 font-semibold text-white">Save affiliate</button></div>
    </form>
</div>
@endsection
