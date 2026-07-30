@extends('platform-admin.layouts.app')

@section('title', 'Global catalog')
@section('eyebrow', 'Platform source records')
@section('heading', 'Global catalog')

@section('content')
<div x-data="globalCatalog()" x-init="load()">
    <div x-show="loading" x-transition class="mb-4 flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800"><span class="h-4 w-4 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></span> Loading global catalog…</div>
    <div class="mb-5 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
        These are the platform’s source product plans and categories. Affiliate-owned copies and their profit settings are managed separately under <a href="{{ route('platform-admin.affiliate-catalog.index') }}" class="font-bold underline">Affiliate catalog</a>.
    </div>
    <div x-show="notice" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800" x-text="notice"></div>
    <div x-show="error" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700" x-text="error"></div>
    <div class="mb-5 flex gap-2 rounded-2xl border border-slate-200 bg-white p-2">
        <template x-for="item in tabs" :key="item.id"><button @click="tab=item.id" :class="tab===item.id?'bg-slate-950 text-white':'text-slate-500'" class="rounded-xl px-4 py-2.5 text-sm font-semibold" x-text="item.label"></button></template>
    </div>

    <section x-show="tab==='plans'" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 p-5"><h2 class="font-semibold">Global product plans</h2><p class="text-sm text-slate-500">Base plan names, provider cost, pricing method and availability to affiliates/customers.</p></div>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Plan</th><th class="p-4">Category</th><th class="p-4">Pricing method</th><th class="p-4">Provider cost</th><th class="p-4">Affiliate profit defaults 1–6</th><th class="p-4">Availability</th><th class="p-4"></th></tr></thead>
            <tbody class="divide-y"><template x-for="plan in plans" :key="plan.id"><tr>
                <td class="p-4"><input x-model="plan.product_plan_name" class="w-52 rounded-lg border-slate-200"></td>
                <td class="p-4"><p x-text="plan.product_plan_category?.product_plan_category_name||'—'"></p><p class="text-xs text-slate-400" x-text="plan.product_plan_category?.network?.network_name||plan.product_plan_category?.product?.product_name"></p></td>
                <td class="p-4"><select x-model="plan.profit_category" class="rounded-lg border-slate-200"><option value="flat">Flat amount added</option><option value="percent">Percentage-based</option></select></td>
                <td class="p-4"><input x-model="plan.cost_price" type="number" min="0" step="0.01" class="w-28 rounded-lg border-slate-200"></td>
                <td class="p-4"><div class="grid min-w-[250px] grid-cols-3 gap-2"><template x-for="level in [1,2,3,4,5,6]"><label class="text-[10px] text-slate-500"><span x-text="'Level '+level"></span><input x-model="plan['aff_level_'+level+'_max_profit']" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-200 px-2 text-sm"></label></template></div></td>
                <td class="p-4"><div class="min-w-[170px] space-y-2 text-xs"><label class="block"><input x-model="plan.visibility" true-value="1" false-value="0" type="checkbox"> Enabled for platform operations</label><label class="block"><input x-model="plan.affiliate_visibility" true-value="1" false-value="0" type="checkbox"> Affiliates may add this plan</label><label class="block"><input x-model="plan.public_visibility" true-value="1" false-value="0" type="checkbox"> Shown in the public plan list</label></div></td>
                <td class="p-4"><button @click="save('plans',plan)" class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white">Save global plan</button></td>
            </tr></template></tbody>
        </table></div>
    </section>

    <section x-show="tab==='categories'" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <template x-for="row in categories" :key="row.id"><div class="rounded-2xl border border-slate-200 bg-white p-5">
            <input x-model="row.product_plan_category_name" class="w-full rounded-xl border-slate-200 font-semibold">
            <p class="mt-2 text-xs text-slate-400" x-text="`${row.product?.product_name||'—'} · ${row.network?.network_name||'No network'}`"></p>
            <div class="mt-4 flex justify-between"><div class="flex gap-3 text-sm"><label><input x-model="row.visibility" true-value="1" false-value="0" type="checkbox"> Enabled globally</label><label><input x-model="row.is_hot_sales" type="checkbox"> Mark hot sale</label></div><button @click="save('categories',row)" class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white">Save</button></div>
        </div></template>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init',()=>Alpine.data('globalCatalog',()=>({
    tab:'plans',tabs:[{id:'plans',label:'Global product plans'},{id:'categories',label:'Global categories'}],plans:[],categories:[],loading:false,notice:'',error:'',
    urls:{data:@js(route('platform-admin.catalog.data')),base:@js(url('/admin/catalog'))},
    async load(){this.loading=true;try{const {data}=await axios.get(this.urls.data);this.plans=data.plans.data;this.categories=data.categories}finally{this.loading=false}},
    async save(type,row){this.error='';try{const {data}=await axios.patch(`${this.urls.base}/${type}/${row.id}`,row);this.notice=data.message;setTimeout(()=>this.notice='',3500)}catch(e){this.error=Object.values(e.response?.data?.errors||{}).flat()[0]||e.response?.data?.message||'Unable to save.'}}
})));
</script>
@endpush
