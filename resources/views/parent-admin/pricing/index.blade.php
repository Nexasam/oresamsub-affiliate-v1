@extends('parent-admin.layouts.app')

@section('title', 'Pricing')
@section('heading', 'Manage reseller pricing')

@section('content')
<div x-data="parentPricing()" x-init="load()" class="space-y-5">
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">These normalized parent prices are ready for the future multi-parent purchase flow. The current live purchase pricing path has not been switched.</div>
    <div x-show="notice" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800" x-text="notice"></div>
    <div x-show="error" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700" x-text="error"></div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-semibold">Reseller levels</h2><p class="text-sm text-slate-500">Keep between one and six levels. Levels already assigned to affiliates or prices cannot be removed.</p></div><div class="flex gap-2"><button @click="addLevel" :disabled="levels.length>=6" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold disabled:opacity-40">Add level</button><button @click="generateSix" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Complete six levels</button><button @click="saveLevels" class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white">Save levels</button></div></div>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3"><template x-for="(level,index) in levels" :key="level.id||`new-${index}`"><div class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-slate-100 text-sm font-bold" x-text="index+1"></span><input x-model="level.name" class="min-w-0 flex-1 rounded-lg border-slate-200"><button x-show="levels.length>1 && index===levels.length-1" @click="levels.splice(index,1);normalizeLevels()" class="text-sm font-semibold text-red-600">Remove</button></div></template></div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5"><div><h2 class="font-semibold">Plan price matrix</h2><p class="text-sm text-slate-500">Set the acquisition price and optional maximum profit for every active reseller level.</p></div><p class="text-sm text-slate-500" x-text="paginationText"></p></div>
        <div x-show="loading" class="p-8 text-center text-sm text-slate-500">Loading pricing…</div>
        <div x-show="!loading" class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="sticky left-0 bg-slate-50 p-4">Plan / provider cost</th><template x-for="level in levels" :key="level.id"><th class="min-w-48 p-4" x-text="level.name"></th></template><th class="p-4"></th></tr></thead><tbody class="divide-y"><template x-for="plan in plans" :key="plan.id"><tr><td class="sticky left-0 bg-white p-4"><p class="font-semibold" x-text="plan.product_plan_name"></p><p class="text-xs text-slate-500" x-text="`Cost: ₦${plan.cost_price||'—'}`"></p></td><template x-for="level in levels" :key="level.id"><td class="p-3"><label class="block text-[10px] uppercase text-slate-400">Selling price<input x-model="priceFor(plan,level).selling_price" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></label><label class="mt-2 block text-[10px] uppercase text-slate-400">Maximum profit<input x-model="priceFor(plan,level).max_profit" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></label></td></template><td class="p-4"><button @click="savePrices(plan)" class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white">Save prices</button></td></tr></template></tbody></table></div>
        <div class="flex items-center justify-between border-t border-slate-100 p-4"><button @click="load(pagination.current_page-1)" :disabled="loading||pagination.current_page<=1" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold disabled:opacity-40">Previous page</button><span class="text-sm text-slate-500" x-text="`Page ${pagination.current_page} of ${pagination.last_page}`"></span><button @click="load(pagination.current_page+1)" :disabled="loading||pagination.current_page>=pagination.last_page" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold disabled:opacity-40">Next page</button></div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init',()=>Alpine.data('parentPricing',()=>({
    levels:[],plans:[],loading:false,notice:'',error:'',pagination:{current_page:1,last_page:1,total:0,from:0,to:0},
    urls:{data:@js(route('parent-admin.pricing.data')),levels:@js(route('parent-admin.pricing.levels.update')),generate:@js(route('parent-admin.pricing.levels.generate-six')),base:@js(url('/parent-admin/pricing/plans'))},
    get paginationText(){return this.pagination.total ? `Showing ${this.pagination.from}–${this.pagination.to} of ${this.pagination.total} plans` : 'No plans found'},
    async load(page=1){this.loading=true;try{const {data}=await axios.get(this.urls.data,{params:{page}});this.levels=data.levels;this.plans=data.plans.data;this.pagination={current_page:data.plans.current_page,last_page:data.plans.last_page,total:data.plans.total,from:data.plans.from||0,to:data.plans.to||0};this.prepare()}catch(e){this.fail(e)}finally{this.loading=false}},
    prepare(){this.plans.forEach(plan=>{plan.price_matrix={};this.levels.forEach(level=>{const price=(plan.parent_prices||[]).find(row=>Number(row.parent_reseller_level_id)===Number(level.id));plan.price_matrix[level.id]={parent_reseller_level_id:level.id,selling_price:price?.selling_price||'',max_profit:price?.max_profit||''}})})},
    priceFor(plan,level){return plan.price_matrix[level.id]||(plan.price_matrix[level.id]={parent_reseller_level_id:level.id,selling_price:'',max_profit:''})},
    normalizeLevels(){this.levels.forEach((level,index)=>level.position=index+1)},
    addLevel(){if(this.levels.length<6){this.levels.push({position:this.levels.length+1,name:`Level ${this.levels.length+1}`})}},
    async saveLevels(){this.error='';try{this.normalizeLevels();const {data}=await axios.put(this.urls.levels,{levels:this.levels});this.levels=data.levels;this.prepare();this.show(data.message)}catch(e){this.fail(e)}},
    async generateSix(){this.error='';try{const {data}=await axios.post(this.urls.generate);this.levels=data.levels;await this.load();this.show(data.message)}catch(e){this.fail(e)}},
    async savePrices(plan){this.error='';try{const prices=this.levels.map(level=>this.priceFor(plan,level));const {data}=await axios.put(`${this.urls.base}/${plan.id}`,{prices});plan.parent_prices=data.prices;this.show(data.message)}catch(e){this.fail(e)}},
    show(message){this.notice=message;setTimeout(()=>this.notice='',3500)},fail(e){this.error=Object.values(e.response?.data?.errors||{}).flat()[0]||e.response?.data?.message||'Unable to complete this action.'}
})));
</script>
@endpush
