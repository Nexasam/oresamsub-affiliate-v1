@extends('parent-admin.layouts.app')

@section('title', 'Product plans')
@section('heading', 'Manage product plans')

@section('content')
<div x-data="parentPlans()" x-init="load()" class="space-y-5">
    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">Only plans owned by <strong>{{ auth('parent_admin')->user()->parentBusiness->name }}</strong> appear here. Categories are defined globally by the platform and can only be selected.</div>
    <div x-show="notice" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800" x-text="notice"></div>
    <div x-show="error" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700" x-text="error"></div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-semibold">Add product plan</h2><p class="text-sm text-slate-500">Create a plan inside this parent's catalogue.</p></div><button @click="createPlan" :disabled="saving" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Add product plan</button></div>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <input x-model="form.product_plan_name" placeholder="Plan name" class="rounded-xl border-slate-200">
            <select x-model="form.product_plan_category_id" class="rounded-xl border-slate-200"><option value="">Select global category</option><template x-for="category in categories" :key="category.id"><option :value="category.id" x-text="categoryLabel(category)"></option></template></select>
            <input x-model="form.cost_price" type="number" min="0" step="0.01" placeholder="Provider cost" class="rounded-xl border-slate-200">
            <select x-model="form.profit_category" class="rounded-xl border-slate-200"><option value="flat">Flat profit</option><option value="percent">Percentage profit</option></select>
            <div class="flex flex-wrap gap-3 text-xs"><label><input x-model="form.visibility" type="checkbox"> Active</label><label><input x-model="form.affiliate_visibility" type="checkbox"> Affiliates</label><label><input x-model="form.public_visibility" type="checkbox"> Public</label></div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5"><div><h2 class="font-semibold">Parent product plans</h2><p class="text-sm text-slate-500" x-text="`${filteredPlans.length} plans shown`"></p></div><input x-model="search" type="search" placeholder="Search plans or categories" class="w-full rounded-xl border-slate-200 sm:w-72"></div>
        <div x-show="loading" class="p-8 text-center text-sm text-slate-500">Loading product plans…</div>
        <div x-show="!loading" class="overflow-x-auto"><table class="w-full min-w-[1050px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Plan</th><th class="p-4">Global category</th><th class="p-4">Provider cost</th><th class="p-4">Profit mode</th><th class="p-4">Availability</th><th class="p-4"></th></tr></thead><tbody class="divide-y"><template x-for="plan in filteredPlans" :key="plan.id"><tr>
            <td class="p-4"><input x-model="plan.product_plan_name" class="w-56 rounded-lg border-slate-200"></td>
            <td class="p-4"><select x-model="plan.product_plan_category_id" class="w-64 rounded-lg border-slate-200"><template x-for="category in categories" :key="category.id"><option :value="category.id" x-text="categoryLabel(category)"></option></template></select></td>
            <td class="p-4"><input x-model="plan.cost_price" type="number" min="0" step="0.01" class="w-28 rounded-lg border-slate-200"></td>
            <td class="p-4"><select x-model="plan.profit_category" class="rounded-lg border-slate-200"><option value="flat">Flat</option><option value="percent">Percent</option></select></td>
            <td class="p-4"><div class="space-y-1 text-xs"><label class="block"><input x-model="plan.visibility" true-value="1" false-value="0" type="checkbox"> Active</label><label class="block"><input x-model="plan.affiliate_visibility" true-value="1" false-value="0" type="checkbox"> Affiliates</label><label class="block"><input x-model="plan.public_visibility" true-value="1" false-value="0" type="checkbox"> Public</label></div></td>
            <td class="p-4"><button @click="save(plan)" :disabled="saving" class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50">Save</button></td>
        </tr></template></tbody></table></div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init',()=>Alpine.data('parentPlans',()=>({
    plans:[],categories:[],loading:false,saving:false,search:'',notice:'',error:'',
    form:{product_plan_name:'',product_plan_category_id:'',cost_price:'',profit_category:'flat',visibility:true,affiliate_visibility:true,public_visibility:true},
    urls:{data:@js(route('parent-admin.product-plans.data')),store:@js(route('parent-admin.product-plans.store')),base:@js(url('/parent-admin/product-plans'))},
    get filteredPlans(){const q=this.search.toLowerCase();return this.plans.filter(p=>`${p.product_plan_name} ${p.product_plan_category?.product_plan_category_name||''}`.toLowerCase().includes(q))},
    categoryLabel(c){return `${c.product_plan_category_name} · ${c.network?.network_name||c.product?.product_name||'General'}`},
    async load(){this.loading=true;this.error='';try{const {data}=await axios.get(this.urls.data);this.plans=data.plans.data;this.categories=data.categories}catch(e){this.fail(e)}finally{this.loading=false}},
    async createPlan(){this.saving=true;this.error='';try{const {data}=await axios.post(this.urls.store,this.form);this.plans.unshift(data.plan);this.form={product_plan_name:'',product_plan_category_id:'',cost_price:'',profit_category:'flat',visibility:true,affiliate_visibility:true,public_visibility:true};this.show(data.message)}catch(e){this.fail(e)}finally{this.saving=false}},
    async save(plan){this.saving=true;this.error='';try{const {data}=await axios.patch(`${this.urls.base}/${plan.id}`,plan);Object.assign(plan,data.plan);this.show(data.message)}catch(e){this.fail(e)}finally{this.saving=false}},
    show(message){this.notice=message;setTimeout(()=>this.notice='',3500)},
    fail(e){this.error=Object.values(e.response?.data?.errors||{}).flat()[0]||e.response?.data?.message||'Unable to complete this action.'}
})));
</script>
@endpush
