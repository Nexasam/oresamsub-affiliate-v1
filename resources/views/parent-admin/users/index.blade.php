@extends('parent-admin.layouts.app')
@section('title', 'Users')
@section('heading', 'Affiliate users')
@section('content')
<div class="space-y-5">
    <div class="rounded-2xl border bg-white p-5">
        <form class="flex flex-wrap gap-3">
            <input name="search" value="{{ request('search') }}" placeholder="Name or email" class="rounded-xl border-slate-200">
            <select name="affiliate_id" class="rounded-xl border-slate-200"><option value="">All affiliates</option>@foreach($affiliates as $affiliate)<option value="{{ $affiliate->id }}" @selected(request('affiliate_id') == $affiliate->id)>{{ $affiliate->name }}</option>@endforeach</select>
            <button class="rounded-xl bg-slate-950 px-4 py-2 text-white">Filter</button>
        </form>
    </div>
    <div class="overflow-x-auto rounded-2xl border bg-white">
        <table class="w-full min-w-[1100px] text-sm"><thead class="bg-slate-50 text-left"><tr><th class="p-4">User</th><th>Affiliate</th><th>Role</th><th>Plan</th><th>Wallet</th><th>Transactions</th><th>Status</th><th>Parent admin edit</th></tr></thead><tbody>
        @forelse($users as $user)<tr class="border-t align-top"><td class="p-4"><strong>{{ $user->first_name }} {{ $user->last_name }}</strong><small class="block text-slate-500">{{ $user->email }}</small><small class="block text-slate-400">{{ $user->phone_number }}</small></td><td>{{ $user->affiliate?->name }}</td><td>{{ $user->role?->role_name }}</td><td>{{ $user->user_plan?->user_plan_name ?: '—' }}</td><td>₦{{ number_format((float) $user->main_wallet, 2) }}</td><td>{{ number_format($user->transactions_count) }}</td><td>{{ $user->active ? 'Active' : 'Inactive' }}</td><td class="p-4"><form method="POST" action="{{ route('parent-admin.users.update', $user->id) }}" class="grid gap-2 md:grid-cols-2">@csrf @method('PATCH')<input name="first_name" value="{{ old('first_name', $user->first_name) }}" required placeholder="First name" class="rounded-lg border-slate-200 text-xs"><input name="last_name" value="{{ old('last_name', $user->last_name) }}" required placeholder="Last name" class="rounded-lg border-slate-200 text-xs"><input name="email" value="{{ old('email', $user->email) }}" required type="email" placeholder="Email" class="rounded-lg border-slate-200 text-xs"><input name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" placeholder="Phone" class="rounded-lg border-slate-200 text-xs"><label class="flex items-center gap-2 text-xs"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked($user->active)> Active</label><button class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white">Save user</button></form></td></tr>
        @empty<tr><td colspan="8" class="p-8 text-center text-slate-500">No users found.</td></tr>@endforelse
        </tbody></table><div class="p-4">{{ $users->links() }}</div>
    </div>
</div>
@endsection
