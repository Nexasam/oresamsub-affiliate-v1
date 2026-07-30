@extends('platform-admin.layouts.app')

@section('title', 'Reports & profit')
@section('eyebrow', 'Platform intelligence')
@section('heading', 'Reports & estimated profit')

@section('content')
<div x-data="profitReport()" x-init="load()">
    <div x-show="loading" x-transition class="mb-4 flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800"><span class="h-4 w-4 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></span> Loading report data…</div>
    <div class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-4">
        <select x-model="filters.affiliate_id" @change="load()" class="rounded-xl border-slate-200 text-sm"><option value="">All affiliates</option>@foreach($affiliates as $affiliate)<option value="{{ $affiliate->id }}">{{ $affiliate->name }}</option>@endforeach</select>
        <input x-model="filters.date_from" @change="load()" type="date" class="rounded-xl border-slate-200 text-sm">
        <input x-model="filters.date_to" @change="load()" type="date" class="rounded-xl border-slate-200 text-sm">
        <button @click="reset()" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Reset filters</button>
    </div>
    <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <template x-for="card in cards()" :key="card[0]"><div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500" x-text="card[0]"></p><p class="mt-2 text-2xl font-bold" x-text="card[1]"></p><p class="mt-1 text-xs text-slate-400" x-text="card[2]"></p></div></template>
    </div>
    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Estimated profit includes only successful transactions with a recorded provider cost. Cost coverage is shown separately so the figure is never presented as complete when source cost is missing.</div>
    <div class="grid gap-5 xl:grid-cols-3">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white xl:col-span-1"><div class="border-b p-5"><h2 class="font-semibold">Affiliate performance</h2></div><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Affiliate</th><th class="p-4">Sales</th><th class="p-4">Revenue</th></tr></thead><tbody class="divide-y"><template x-for="row in affiliates" :key="row.affiliate_id"><tr><td class="p-4 font-medium" x-text="row.name"></td><td class="p-4" x-text="number(row.transactions)"></td><td class="p-4 font-semibold" x-text="money(row.revenue)"></td></tr></template></tbody></table></section>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white xl:col-span-2"><div class="border-b p-5"><h2 class="font-semibold">Recent successful sales</h2></div><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Affiliate</th><th class="p-4">Reference</th><th class="p-4">Category</th><th class="p-4">Revenue</th><th class="p-4">Provider cost</th><th class="p-4">Date</th></tr></thead><tbody class="divide-y"><template x-for="row in recent" :key="row.id"><tr><td class="p-4" x-text="row.affiliate?.name||'—'"></td><td class="p-4 font-mono text-xs" x-text="row.txn_reference||'#'+row.id"></td><td class="p-4 capitalize" x-text="row.transaction_category||'—'"></td><td class="p-4 font-semibold" x-text="money(row.discounted_amount||row.amount)"></td><td class="p-4" x-text="row.automation_plan_amount!==null?money(row.automation_plan_amount):'Unavailable'"></td><td class="p-4 text-slate-500" x-text="date(row.created_at)"></td></tr></template></tbody></table></div></section>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init',()=>Alpine.data('profitReport',()=>({
    filters:{affiliate_id:@js(request('affiliate_id','')),date_from:'',date_to:''},summary:{},affiliates:[],recent:[],loading:false,
    async load(){this.loading=true;try{const {data}=await axios.get(@js(route('platform-admin.reports.data'))+'?'+new URLSearchParams(this.filters));this.summary=data.summary;this.affiliates=data.by_affiliate;this.recent=data.recent}finally{this.loading=false}},
    reset(){this.filters={affiliate_id:'',date_from:'',date_to:''};this.load()},
    cards(){return [['Successful sales',this.number(this.summary.transactions),'Filtered period'],['Revenue',this.money(this.summary.revenue),'Customer charges'],['Provider cost',this.money(this.summary.provider_cost),'Recorded costs only'],['Estimated profit',this.money(this.summary.estimated_profit),'On cost-covered sales'],['Cost coverage',(this.summary.coverage_percent||0)+'%',(this.summary.cost_coverage||0)+' sales covered']]},
    money(v){return new Intl.NumberFormat('en-NG',{style:'currency',currency:'NGN'}).format(Number(v||0))},number(v){return new Intl.NumberFormat('en-NG').format(Number(v||0))},date(v){return v?new Intl.DateTimeFormat('en-NG',{dateStyle:'medium'}).format(new Date(v)):'—'}
})));
</script>
@endpush
