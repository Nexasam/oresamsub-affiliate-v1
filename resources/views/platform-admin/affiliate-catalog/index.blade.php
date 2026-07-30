@extends('platform-admin.layouts.app')

@section('title', 'Affiliate catalog')
@section('eyebrow', 'Affiliate-owned records')
@section('heading', 'Affiliate product plans & categories')

@section('content')
<div x-data="affiliateCatalog()" x-init="selectedAffiliate && load()">
    <div x-show="loading" x-transition class="mb-4 flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800"><span class="h-4 w-4 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></span> Loading affiliate plans and categories…</div>
    <div class="mb-5 rounded-2xl border border-slate-200 bg-white p-5">
        <label class="block text-sm font-semibold text-slate-700">Affiliate to manage</label>
        <div class="mt-2 flex flex-wrap gap-3">
            <select x-model="selectedAffiliate" @change="page=1;load()" class="min-w-72 rounded-xl border-slate-200">
                <option value="">Select an affiliate</option>
                @foreach($affiliates as $affiliate)<option value="{{ $affiliate->id }}">#{{ $affiliate->id }} — {{ $affiliate->name }}</option>@endforeach
            </select>
            <input x-model="search" @input.debounce.400ms="page=1;load()" :disabled="!selectedAffiliate" placeholder="Search this affiliate's plans…" class="min-w-72 rounded-xl border-slate-200 text-sm disabled:bg-slate-100">
        </div>
        <p class="mt-2 text-sm text-slate-500">Every record below belongs to the selected affiliate. Changes do not affect other affiliates or the global source catalog.</p>
    </div>

    <div x-show="notice" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800" x-text="notice"></div>
    <div x-show="error" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700" x-text="error"></div>
    <div class="mb-5 flex gap-2 rounded-2xl border border-slate-200 bg-white p-2">
        <button @click="tab='plans'" :class="tab==='plans'?'bg-slate-950 text-white':'text-slate-500'" class="rounded-xl px-4 py-2.5 text-sm font-semibold">Affiliate product plans</button>
        <button @click="tab='categories'" :class="tab==='categories'?'bg-slate-950 text-white':'text-slate-500'" class="rounded-xl px-4 py-2.5 text-sm font-semibold">Affiliate categories</button>
    </div>

    <div x-show="!selectedAffiliate" class="rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center text-slate-500">Select an affiliate to view and edit its catalog.</div>

    <section x-show="selectedAffiliate && tab==='plans'" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5"><div><h2 class="font-semibold">Plans owned by the selected affiliate</h2><p class="text-sm text-slate-500">Edit the affiliate’s displayed name, profit for each user level, customer availability, and referral commission.</p></div><div class="text-right"><p class="mb-2 text-xs text-slate-500"><span x-text="planTotal"></span> of <span x-text="sourceCounts.plans"></span> global plans generated</p><button @click="generatePlans()" class="rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-white">Generate missing product plans</button></div></div>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Affiliate plan name</th><th class="p-4">Global source</th><th class="p-4">Profit levels 1–6</th><th class="p-4">Commission</th><th class="p-4">Customer availability</th><th class="p-4"></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <template x-for="plan in plans" :key="plan.id"><tr class="align-top">
                    <td class="p-4"><input x-model="plan.product_plan_name" class="w-48 rounded-lg border-slate-200"><p class="mt-1 text-xs capitalize text-slate-400" x-text="(plan.product_plan?.profit_category||'flat')+' profit'"></p></td>
                    <td class="p-4"><p class="font-medium" x-text="plan.product_plan?.product_plan_name||'—'"></p><p class="text-xs text-slate-400" x-text="`${plan.product_plan?.product_plan_category?.product_plan_category_name||'—'} · ${plan.product_plan?.product_plan_category?.network?.network_name||plan.product_plan?.product_plan_category?.product?.product_name||'—'}`"></p></td>
                    <td class="p-4"><div class="grid min-w-[250px] grid-cols-3 gap-2"><template x-for="level in [1,2,3,4,5,6]"><label class="text-[10px] text-slate-500"><span x-text="'Level '+level"></span><input x-model="plan['user_level_'+level+'_profit']" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-200 px-2 text-sm"></label></template></div></td>
                    <td class="p-4"><div class="min-w-[180px] space-y-2"><label class="block text-xs"><input x-model="plan.commission_feature" true-value="1" false-value="0" type="checkbox"> Referral commission enabled</label><select x-model="plan.upline_commission_option" class="w-full rounded-lg border-slate-200 text-xs"><option value="flat">Flat commission</option><option value="percent">Percentage commission</option><option value="percentage">Percentage commission</option></select><input x-show="plan.upline_commission_option==='flat'" x-model="plan.upline_flat_commission" type="number" min="0" step="0.01" placeholder="Flat value" class="w-full rounded-lg border-slate-200 text-xs"><input x-show="plan.upline_commission_option!=='flat'" x-model="plan.upline_percentage_commission" type="number" min="0" max="100" step="0.01" placeholder="Percent value" class="w-full rounded-lg border-slate-200 text-xs"></div></td>
                    <td class="p-4"><div class="min-w-[160px] space-y-2 text-xs"><label class="block"><input x-model="plan.visibility" true-value="1" false-value="0" type="checkbox"> Available to logged-in customers</label><label class="block"><input x-model="plan.public_visibility" true-value="1" false-value="0" type="checkbox"> Visible without login</label></div></td>
                    <td class="p-4"><button @click="savePlan(plan)" class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white">Save affiliate plan</button></td>
                </tr></template>
                <template x-if="!loading && plans.length===0"><tr><td colspan="6" class="p-12 text-center text-slate-400">This affiliate has no matching product plans.</td></tr></template>
            </tbody>
        </table></div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4 text-sm">
            <p class="text-slate-500">Page <span class="font-semibold text-slate-700" x-text="page"></span> of <span class="font-semibold text-slate-700" x-text="lastPage"></span> · <span x-text="filteredTotal"></span> matching plans</p>
            <div class="flex gap-2"><button @click="changePage(page-1)" :disabled="page<=1||loading" class="rounded-lg border border-slate-200 px-3 py-2 font-semibold disabled:opacity-40">← Previous</button><button @click="changePage(page+1)" :disabled="page>=lastPage||loading" class="rounded-lg border border-slate-200 px-3 py-2 font-semibold disabled:opacity-40">Next →</button></div>
        </div>
    </section>

    <section x-show="selectedAffiliate && tab==='categories'">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4"><p class="text-sm text-slate-600"><span x-text="categories.length"></span> of <span x-text="sourceCounts.categories"></span> global categories generated.</p><button @click="generateCategories()" class="rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-white">Generate missing categories</button></div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <template x-for="category in categories" :key="category.id"><form @submit.prevent="saveCategory(category)" class="rounded-2xl border border-slate-200 bg-white p-5">
            <label class="text-xs font-semibold uppercase text-slate-400">Affiliate category name</label>
            <input x-model="category.product_plan_category_name" class="mt-1 w-full rounded-xl border-slate-200 font-semibold">
            <p class="mt-2 text-xs text-slate-400" x-text="`${category.product?.product_name||'—'} · ${category.network?.network_name||'No network'}`"></p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="text-sm">Referral method<select x-model="category.referral_commission_method" class="mt-1 w-full rounded-xl border-slate-200"><option value="flat">Flat</option><option value="percent">Percentage</option><option value="percentage">Percentage</option></select></label>
                <label class="text-sm">Referral value<input x-model="category.referral_commission_value" type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-200"></label>
            </div>
            <div class="mt-4 flex items-center justify-between"><div class="space-y-2 text-sm"><label class="block"><input x-model="category.visibility" true-value="1" false-value="0" type="checkbox"> Available to customers</label><label class="block"><input x-model="category.is_hot_sales" type="checkbox"> Mark as hot sale</label></div><button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Save category</button></div>
        </form></template>
        <template x-if="!loading && categories.length===0"><div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white py-14 text-center text-slate-400">This affiliate has no categories.</div></template>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init',()=>Alpine.data('affiliateCatalog',()=>({
    selectedAffiliate:@js((string) request('affiliate_id', $affiliates->first()?->id ?? '')),tab:'plans',search:'',plans:[],planTotal:0,page:1,lastPage:1,filteredTotal:0,categories:[],sourceCounts:{plans:0,categories:0},loading:false,notice:'',error:'',
    base:@js(url('/admin/affiliates')),
    async request(method,url,data=null){this.error='';try{return await axios({method,url,data})}catch(e){this.error=Object.values(e.response?.data?.errors||{}).flat()[0]||e.response?.data?.message||'Unable to complete request.';throw e}},
    async load(){if(!this.selectedAffiliate){this.plans=[];this.planTotal=0;this.categories=[];return}this.loading=true;try{const params=new URLSearchParams({search:this.search,page:this.page});const {data}=await this.request('get',`${this.base}/${this.selectedAffiliate}/catalog?${params}`);this.plans=data.plans.data;this.planTotal=data.generated_counts.plans;this.page=data.plans.current_page;this.lastPage=data.plans.last_page;this.filteredTotal=data.plans.total;this.categories=data.categories;this.sourceCounts=data.source_counts}finally{this.loading=false}},
    async changePage(page){if(page<1||page>this.lastPage)return;this.page=page;await this.load()},
    async savePlan(plan){const {data}=await this.request('patch',`${this.base}/${this.selectedAffiliate}/catalog/plans/${plan.id}`,plan);this.flash(data.message)},
    async saveCategory(category){const {data}=await this.request('patch',`${this.base}/${this.selectedAffiliate}/catalog/categories/${category.id}`,category);this.flash(data.message)},
    async generatePlans(){const {data}=await this.request('post',`${this.base}/${this.selectedAffiliate}/catalog/plans/generate`);this.flash(data.message);await this.load()},
    async generateCategories(){const {data}=await this.request('post',`${this.base}/${this.selectedAffiliate}/catalog/categories/generate`);this.flash(data.message);await this.load()},
    flash(message){this.notice=message;setTimeout(()=>this.notice='',3500)}
})));
</script>
@endpush
