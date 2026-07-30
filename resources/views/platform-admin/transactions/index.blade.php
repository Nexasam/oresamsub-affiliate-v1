@extends('platform-admin.layouts.app')

@section('title', 'Transactions')
@section('eyebrow', 'Operations')
@section('heading', 'All transactions')
@section('content')
<div x-data="transactionIndex()" x-init="load()">
    <div x-show="loading" x-transition class="mb-4 flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800"><span class="h-4 w-4 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></span> Loading transactions…</div>
    <div x-show="notice" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" x-text="notice"></div>
    <div x-show="error" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="error"></div>
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
            <input x-model="filters.search" @input.debounce.500ms="load()" placeholder="Reference, email, phone…" class="rounded-xl border-slate-200 text-sm">
            <select x-model="filters.affiliate_id" @change="load()" class="rounded-xl border-slate-200 text-sm"><option value="">All affiliates</option>@foreach($affiliates as $affiliate)<option value="{{ $affiliate->id }}">{{ $affiliate->name }}</option>@endforeach</select>
            <select x-model="filters.status" @change="load()" class="rounded-xl border-slate-200 text-sm"><option value="">All statuses</option><option value="1">Successful</option><option value="0">Pending</option><option value="-1">Failed</option><option value="2">Refunded</option><option value="3">Processing</option></select>
            <select x-model="filters.category" @change="load()" class="rounded-xl border-slate-200 text-sm"><option value="">All categories</option><option value="data">Data</option><option value="airtime">Airtime</option><option value="cable_subscription">Cable</option><option value="utility_bills">Electricity</option></select>
            <input x-model="filters.date_from" @change="load()" type="date" class="rounded-xl border-slate-200 text-sm">
            <input x-model="filters.date_to" @change="load()" type="date" class="rounded-xl border-slate-200 text-sm">
            <input x-model="filters.amount_min" @change="load()" type="number" min="0" placeholder="Minimum amount" class="rounded-xl border-slate-200 text-sm">
            <input x-model="filters.amount_max" @change="load()" type="number" min="0" placeholder="Maximum amount" class="rounded-xl border-slate-200 text-sm">
            <button @click="reset()" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold">Clear filters</button>
        </div>
    </section>

    <div class="my-5 grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Matching transactions</p><p class="mt-2 text-2xl font-bold" x-text="summary.count.toLocaleString()"></p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Matching value</p><p class="mt-2 text-2xl font-bold" x-text="money(summary.amount)"></p></div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-4">Reference</th><th class="px-5 py-4">Affiliate</th><th class="px-5 py-4">Customer</th><th class="px-5 py-4">Category</th><th class="px-5 py-4">Amount</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Date</th><th class="px-5 py-4"></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <template x-if="loading"><tr><td colspan="8" class="px-5 py-12 text-center text-slate-400"><span class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-emerald-500 border-t-transparent"></span><p class="mt-2">Loading transaction records…</p></td></tr></template>
                <template x-for="row in rows" :key="row.id"><tr x-show="!loading"><td class="px-5 py-4 font-mono text-xs" x-text="row.txn_reference || '#'+row.id"></td><td class="px-5 py-4 font-medium" x-text="row.affiliate?.name || '—'"></td><td class="px-5 py-4"><p x-text="row.user ? row.user.first_name+' '+row.user.last_name : '—'"></p><p class="text-xs text-slate-400" x-text="row.user?.email"></p></td><td class="px-5 py-4 capitalize" x-text="row.transaction_category || '—'"></td><td class="px-5 py-4 font-semibold" x-text="money(row.amount)"></td><td class="px-5 py-4" x-text="status(row.status)"></td><td class="px-5 py-4 text-slate-500" x-text="date(row.created_at)"></td><td class="px-5 py-4"><button @click="openStatus(row)" class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white">Update status</button></td></tr></template>
                <template x-if="!loading && !rows.length"><tr><td colspan="8" class="px-5 py-12 text-center text-slate-400">No matching transactions.</td></tr></template>
            </tbody>
        </table></div>
        <div class="flex items-center justify-between border-t border-slate-100 p-4"><button @click="page--; load()" :disabled="page <= 1" class="rounded-lg border px-3 py-2 text-sm disabled:opacity-40">Previous</button><span class="text-sm text-slate-500" x-text="`Page ${page} of ${lastPage}`"></span><button @click="page++; load()" :disabled="page >= lastPage" class="rounded-lg border px-3 py-2 text-sm disabled:opacity-40">Next</button></div>
    </section>
    <div x-cloak x-show="showStatusModal" class="fixed inset-0 z-[90] grid place-items-center bg-slate-950/60 p-4" @keydown.escape.window="showStatusModal=false">
        <form @submit.prevent="saveStatus()" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex justify-between"><div><h2 class="text-lg font-bold">Update transaction status</h2><p class="text-sm text-slate-500" x-text="selectedTransaction?.txn_reference||'#'+selectedTransaction?.id"></p></div><button type="button" @click="showStatusModal=false" class="text-xl text-slate-400">×</button></div>
            <label class="mt-5 block text-sm font-medium">New status<select x-model="statusForm.status" class="mt-1 w-full rounded-xl border-slate-200"><option value="1">Successful</option><option value="0">Pending</option><option value="-1">Failed</option><option value="2">Refunded</option><option value="3">Processing</option></select></label>
            <label class="mt-4 block text-sm font-medium">Reason / audit note<textarea x-model="statusForm.reason" required minlength="5" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Explain why this status is being changed"></textarea></label>
            <label class="mt-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><input x-model="statusForm.impact_wallet" type="checkbox" class="mt-1"><span><b>Apply the corresponding wallet impact.</b><br>Refunded/failed credits the deducted amount once. Successful reverses an earlier refund credit. Leave unchecked to change only the status.</span></label>
            <p x-show="statusForm.impact_wallet" class="mt-3 text-xs text-red-600">This is a financial action and will create an audited wallet log.</p>
            <div class="mt-6 flex justify-end gap-3"><button type="button" @click="showStatusModal=false" class="px-4 py-2 text-sm font-semibold text-slate-500">Cancel</button><button :disabled="saving" class="rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50" x-text="saving?'Updating…':'Confirm update'"></button></div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('alpine:init', () => Alpine.data('transactionIndex', () => ({
    filters:{search:'',affiliate_id:'',status:'',category:'',date_from:'',date_to:'',amount_min:'',amount_max:''}, rows:[], summary:{count:0,amount:0}, page:1,lastPage:1,loading:false,saving:false,notice:'',error:'',showStatusModal:false,selectedTransaction:null,statusForm:{status:'',impact_wallet:false,reason:''},
    async load(){this.loading=true;this.error='';const params=new URLSearchParams({...this.filters,page:this.page});try{const {data}=await axios.get(@js(route('platform-admin.transactions.data'))+'?'+params);this.rows=data.transactions.data;this.lastPage=data.transactions.last_page;this.summary=data.summary}catch(e){this.error=e.response?.data?.message||'Unable to load transactions'}finally{this.loading=false}},
    openStatus(row){this.selectedTransaction=row;this.statusForm={status:String(row.status),impact_wallet:false,reason:''};this.showStatusModal=true},
    async saveStatus(){this.saving=true;this.error='';try{const {data}=await axios.patch(@js(url('/admin/all-transactions'))+`/${this.selectedTransaction.id}/status`,this.statusForm);Object.assign(this.selectedTransaction,data.transaction);this.notice=data.message;this.showStatusModal=false;setTimeout(()=>this.notice='',5000)}catch(e){this.error=Object.values(e.response?.data?.errors||{}).flat()[0]||e.response?.data?.message||'Unable to update transaction'}finally{this.saving=false}},
    reset(){this.filters={search:'',affiliate_id:'',status:'',category:'',date_from:'',date_to:'',amount_min:'',amount_max:''};this.page=1;this.load()},
    money(v){return new Intl.NumberFormat('en-NG',{style:'currency',currency:'NGN'}).format(Number(v||0))}, date(v){return new Date(v).toLocaleString('en-NG')}, status(v){return ({'1':'Successful','0':'Pending','-1':'Failed','2':'Refunded','3':'Processing'})[String(v)]||v}
})));
</script>
@endpush
