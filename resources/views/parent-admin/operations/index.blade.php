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
    @if($selected)
    <section class="overflow-hidden rounded-2xl border bg-white shadow-sm">
        <div class="border-b p-5">
            <h2 class="font-semibold">Affiliate accounts</h2>
            <p class="mt-1 text-sm text-slate-500">Securely open an administrator or customer account for {{ $selected->name }}. Access links expire after two minutes and can be used only once.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Account</th><th class="px-5 py-3">Role</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Access</th></tr></thead>
                <tbody class="divide-y">
                @forelse($accounts as $account)
                    <tr>
                        <td class="px-5 py-4"><p class="font-semibold">{{ $account->first_name }} {{ $account->last_name }}</p><p class="text-xs text-slate-500">{{ $account->email }} · {{ '@'.$account->username }}</p></td>
                        <td class="px-5 py-4">{{ $account->role?->role_name ?? 'Unassigned' }}</td>
                        <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ (int) $account->active === 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ (int) $account->active === 1 ? 'Active' : 'Deactivated' }}</span></td>
                        <td class="px-5 py-4 text-right"><form method="POST" target="_blank" action="{{ route('parent-admin.affiliates.users.impersonate', [$selected, $account]) }}">@csrf<button class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white">{{ $account->role?->role_name === 'Admin' ? 'Login as affiliate admin' : 'Login as user' }}</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">No accounts found for this affiliate.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($accounts->hasPages())<div class="border-t p-4">{{ $accounts->links() }}</div>@endif
    </section>
    @endif
</div>
@endsection
