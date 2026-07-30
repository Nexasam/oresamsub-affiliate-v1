@extends('platform-admin.layouts.app')

@section('title', 'Operations')
@section('eyebrow', 'Platform operations')
@section('heading', 'Operations')

@section('content')
<div x-data="affiliateOperations()" x-init="loadWalletLogs()">
    <div x-show="loading" x-transition class="mb-4 flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800"><span class="h-4 w-4 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></span> Loading affiliate operations data…</div>
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-5">
        <div class="min-w-72">
            <label class="block text-sm font-semibold text-slate-700">Affiliate</label>
            <select onchange="window.location='{{ route('platform-admin.operations.index') }}?affiliate_id='+this.value" class="mt-1 w-full rounded-xl border-slate-200">
                @foreach($affiliates as $item)<option value="{{ $item->id }}" @selected($item->id === $affiliate->id)>#{{ $item->id }} — {{ $item->name }}</option>@endforeach
            </select>
            <p class="mt-2 text-sm text-slate-500">Wallet activity and product-margin defaults for the selected affiliate.</p>
        </div>
        <a href="{{ route('platform-admin.affiliates.show', $affiliate) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold">View affiliate details</a>
    </div>

    <div x-show="notice" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800" x-text="notice"></div>
    <div x-show="error" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700" x-text="error"></div>

    <div class="mb-5 flex gap-2 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2">
        <template x-for="item in tabs" :key="item.id"><button @click="select(item.id)" :class="tab===item.id?'bg-slate-950 text-white':'text-slate-500 hover:bg-slate-50'" class="whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-semibold" x-text="item.label"></button></template>
    </div>

    <section x-show="false" class="hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5">
            <div><h2 class="font-semibold">Affiliate product plans</h2><p class="text-sm text-slate-500">Edit all six customer-level margins, visibility and commissions.</p></div>
            <input x-model="search" @input.debounce.400ms="loadCatalog()" placeholder="Search plans…" class="rounded-xl border-slate-200 text-sm">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Plan</th><th class="px-4 py-3">Product / network</th><th class="px-4 py-3">Margin type</th><th class="px-4 py-3">Level margins 1–6</th><th class="px-4 py-3">Controls</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="plan in plans" :key="plan.id"><tr class="align-top">
                        <td class="px-4 py-4"><input x-model="plan.product_plan_name" class="w-56 rounded-lg border-slate-200 text-sm"><p class="mt-1 text-xs text-slate-400" x-text="plan.product_plan?.product_plan_category?.product_plan_category_name"></p></td>
                        <td class="px-4 py-4"><p x-text="plan.product_plan?.product_plan_category?.product?.product_name || '—'"></p><p class="text-xs text-slate-400" x-text="plan.product_plan?.product_plan_category?.network?.network_name || 'No network'"></p></td>
                        <td class="px-4 py-4 capitalize" x-text="plan.product_plan?.profit_category || 'flat'"></td>
                        <td class="px-4 py-4"><div class="flex min-w-[360px] gap-1"><template x-for="level in [1,2,3,4,5,6]"><input x-model="plan['user_level_'+level+'_profit']" type="number" min="0" step="0.01" class="w-14 rounded-lg border-slate-200 px-2 text-sm"></template></div></td>
                        <td class="px-4 py-4"><div class="flex items-center gap-3"><label class="text-xs"><input x-model="plan.visibility" true-value="1" false-value="0" type="checkbox"> Visible</label><label class="text-xs"><input x-model="plan.public_visibility" true-value="1" false-value="0" type="checkbox"> Public</label><button @click="savePlan(plan)" class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white">Save</button></div></td>
                    </tr></template>
                    <template x-if="!loading && plans.length===0"><tr><td colspan="5" class="p-10 text-center text-slate-400">No affiliate plans found.</td></tr></template>
                </tbody>
            </table>
        </div>
    </section>

    <section x-show="false" class="hidden">
        <template x-for="category in categories" :key="category.id"><form @submit.prevent="saveCategory(category)" class="rounded-2xl border border-slate-200 bg-white p-5">
            <input x-model="category.product_plan_category_name" class="w-full rounded-xl border-slate-200 font-semibold">
            <p class="mt-2 text-xs text-slate-400" x-text="`${category.product?.product_name || 'Product'} · ${category.network?.network_name || 'No network'}`"></p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="text-sm">Commission method<select x-model="category.referral_commission_method" class="mt-1 w-full rounded-xl border-slate-200"><option value="flat">Flat</option><option value="percent">Percentage</option></select></label>
                <label class="text-sm">Commission value<input x-model="category.referral_commission_value" type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-200"></label>
            </div>
            <div class="mt-4 flex items-center justify-between"><div class="flex gap-4 text-sm"><label><input x-model="category.visibility" true-value="1" false-value="0" type="checkbox"> Visible</label><label><input x-model="category.is_hot_sales" type="checkbox"> Hot sale</label></div><button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Save</button></div>
        </form></template>
    </section>

    <section x-show="tab==='wallets'" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="flex gap-3 border-b border-slate-100 p-5"><input x-model="walletSearch" @input.debounce.400ms="loadWalletLogs()" placeholder="User, reference or description…" class="w-full max-w-md rounded-xl border-slate-200 text-sm"></div>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">User</th><th class="p-4">Category</th><th class="p-4">Before</th><th class="p-4">After</th><th class="p-4">Change</th><th class="p-4">Description</th><th class="p-4">Date</th></tr></thead><tbody class="divide-y divide-slate-100"><template x-for="log in walletLogs" :key="log.id"><tr><td class="p-4"><p x-text="log.user?`${log.user.first_name} ${log.user.last_name}`:'—'"></p><p class="text-xs text-slate-400" x-text="log.user?.email"></p></td><td class="p-4" x-text="log.transaction_category"></td><td class="p-4" x-text="money(log.balance_before)"></td><td class="p-4" x-text="money(log.balance_after)"></td><td class="p-4 font-semibold" :class="Number(log.balance_after)-Number(log.balance_before)>=0?'text-emerald-600':'text-red-600'" x-text="money(Number(log.balance_after)-Number(log.balance_before))"></td><td class="max-w-sm p-4 text-slate-500" x-text="log.description"></td><td class="p-4 text-slate-500" x-text="date(log.created_at)"></td></tr></template></tbody></table></div>
    </section>

    <section x-show="tab==='margins'" class="grid gap-5 lg:grid-cols-2">
        <form @submit.prevent="saveMargins()" class="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="font-semibold">Default product margins</h2><p class="mt-1 text-sm text-slate-500">Used whenever a product plan is newly added or synced for this affiliate.</p>
            <label class="mt-5 block text-sm font-medium">Flat-rate default (₦)<input x-model="margins.default_flat_profit_margin" type="number" min="0" step="0.01" required class="mt-1 w-full rounded-xl border-slate-200"></label>
            <label class="mt-4 block text-sm font-medium">Percentage default (%)<input x-model="margins.default_percent_profit_margin" type="number" min="0" max="100" step="0.01" required class="mt-1 w-full rounded-xl border-slate-200"></label>
            <label class="mt-5 flex items-start gap-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-900"><input x-model="margins.apply_to_existing" type="checkbox" class="mt-1"> Replace all six margin levels on every existing affiliate plan. Leave unchecked to preserve custom prices.</label>
            <button class="mt-5 w-full rounded-xl bg-emerald-500 px-5 py-3 font-semibold text-white">Save margin defaults</button>
        </form>
        <div class="rounded-2xl bg-slate-950 p-6 text-white"><h2 class="font-semibold">Pricing behavior</h2><p class="mt-3 text-sm leading-6 text-slate-300">Data and cable plans use the flat-rate margin. Airtime and electricity use the percentage margin. These settings belong only to {{ $affiliate->name }} and do not affect other affiliates.</p><a href="{{ route('platform-admin.reports.index', ['affiliate_id' => $affiliate->id]) }}" class="mt-6 inline-block rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/20">View profit report →</a></div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init',()=>Alpine.data('affiliateOperations',()=>({
        tab:'wallets',tabs:[{id:'wallets',label:'Wallet logs'},{id:'margins',label:'Margin defaults'}],
    plans:[],categories:[],walletLogs:[],search:'',walletSearch:'',loading:false,notice:'',error:'',
    margins:{default_flat_profit_margin:@js($affiliate->default_flat_profit_margin ?? 50),default_percent_profit_margin:@js($affiliate->default_percent_profit_margin ?? 1),apply_to_existing:false},
    urls:{catalog:@js(route('platform-admin.affiliates.catalog',$affiliate)),wallets:@js(route('platform-admin.affiliates.wallet-logs',$affiliate)),plans:@js(url('/admin/affiliates/'.$affiliate->id.'/catalog/plans')),categories:@js(url('/admin/affiliates/'.$affiliate->id.'/catalog/categories')),margins:@js(route('platform-admin.affiliates.margin-defaults.update',$affiliate))},
    async request(method,url,data=null){this.error='';try{return await axios({method,url,data})}catch(e){this.error=Object.values(e.response?.data?.errors||{}).flat()[0]||e.response?.data?.message||'Unable to complete request.';throw e}},
    async select(tab){this.tab=tab;if(tab==='wallets')await this.loadWalletLogs();if(tab==='plans'||tab==='categories')await this.loadCatalog()},
    async loadCatalog(){this.loading=true;try{const {data}=await this.request('get',this.urls.catalog+'?search='+encodeURIComponent(this.search));this.plans=data.plans.data;this.categories=data.categories}finally{this.loading=false}},
    async loadWalletLogs(){this.loading=true;try{const {data}=await this.request('get',this.urls.wallets+'?search='+encodeURIComponent(this.walletSearch));this.walletLogs=data.data}finally{this.loading=false}},
    async savePlan(plan){const {data}=await this.request('patch',`${this.urls.plans}/${plan.id}`,plan);this.flash(data.message)},
    async saveCategory(category){const {data}=await this.request('patch',`${this.urls.categories}/${category.id}`,category);this.flash(data.message)},
    async saveMargins(){const {data}=await this.request('patch',this.urls.margins,this.margins);this.margins.apply_to_existing=false;this.flash(data.message);if(data.updated_plans)await this.loadCatalog()},
    flash(message){this.notice=message;setTimeout(()=>this.notice='',4000)},money(v){return new Intl.NumberFormat('en-NG',{style:'currency',currency:'NGN'}).format(Number(v||0))},date(v){return v?new Intl.DateTimeFormat('en-NG',{dateStyle:'medium',timeStyle:'short'}).format(new Date(v)):'—'}
})));
</script>
@endpush
