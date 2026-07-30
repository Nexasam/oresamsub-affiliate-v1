@extends('platform-admin.layouts.app')

@section('title', 'Affiliate users')
@section('eyebrow', 'Cross-affiliate user management')
@section('heading', 'Affiliate users & user plans')

@section('content')
<div x-data="affiliateUsers()" x-init="load()">
    <div x-show="loading" x-transition class="mb-4 flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800"><span class="h-4 w-4 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></span> Loading affiliate users and plans…</div>
    <div class="mb-5 rounded-2xl border border-slate-200 bg-white p-5">
        <label class="block text-sm font-semibold">Affiliate to manage</label>
        <div class="mt-2 flex flex-wrap gap-3">
            <select x-model="selectedAffiliate" @change="page=1;load()" class="min-w-72 rounded-xl border-slate-200"><option value="">All affiliates</option>@foreach($affiliates as $affiliate)<option value="{{ $affiliate->id }}">#{{ $affiliate->id }} — {{ $affiliate->name }}</option>@endforeach</select>
            <input x-model="search" @input.debounce.400ms="page=1;load()" placeholder="Search name, email, username or phone…" class="min-w-80 flex-1 rounded-xl border-slate-200 text-sm">
            <button @click="openCreate()" :disabled="!selectedAffiliate" class="rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40">+ Add user</button>
        </div>
        <p class="mt-2 text-sm text-slate-500">Users can only be assigned to user plans owned by the selected affiliate.</p>
    </div>
    <div x-show="notice" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800" x-text="notice"></div>
    <div x-show="error" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700" x-text="error"></div>
    <div class="mb-5 flex gap-2 rounded-2xl border border-slate-200 bg-white p-2"><button @click="tab='users'" :class="tab==='users'?'bg-slate-950 text-white':'text-slate-500'" class="rounded-xl px-4 py-2.5 text-sm font-semibold">Users</button><button @click="tab='plans'" :class="tab==='plans'?'bg-slate-950 text-white':'text-slate-500'" class="rounded-xl px-4 py-2.5 text-sm font-semibold">Affiliate user plans</button></div>
    <section x-show="tab==='users'" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 p-5"><h2 class="font-semibold">Users of the selected affiliate</h2><p class="text-sm text-slate-500">Edit identity, account access, role, assigned affiliate plan, verification and customer settings.</p></div>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">User</th><th class="p-4">Affiliate</th><th class="p-4">Affiliate plan</th><th class="p-4">Role</th><th class="p-4">Wallet</th><th class="p-4">Activity</th><th class="p-4">Status</th><th class="p-4"></th></tr></thead><tbody class="divide-y">
            <template x-for="user in users" :key="user.id"><tr><td class="p-4"><p class="font-semibold" x-text="`${user.first_name} ${user.last_name}`"></p><p class="text-xs text-slate-400" x-text="user.email"></p><p class="text-xs text-slate-400" x-text="'@'+user.username"></p></td><td class="p-4"><p class="font-medium" x-text="user.affiliate?.name||'—'"></p><p class="text-xs text-slate-400" x-text="'Affiliate #'+user.affiliate_id"></p></td><td class="p-4"><p x-text="planName(user.user_plan)"></p><p class="text-xs text-slate-400" x-text="user.user_plan?'Level '+user.user_plan.plan_level:'No plan assigned'"></p></td><td class="p-4" x-text="user.role?.role_name||'—'"></td><td class="p-4 font-semibold" x-text="money(user.main_wallet)"></td><td class="p-4"><p x-text="number(user.transactions_count)+' transactions'"></p><p class="text-xs text-slate-400" x-text="number(user.referrals_count)+' referrals'"></p></td><td class="p-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="Number(user.active)===1?'bg-emerald-50 text-emerald-700':'bg-red-50 text-red-700'" x-text="Number(user.active)===1?'Active':'Deactivated'"></span></td><td class="p-4"><div class="flex min-w-[210px] flex-wrap gap-2"><button @click="openEdit(user)" class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white">Edit</button><button @click="openCredit(user)" class="rounded-lg bg-emerald-500 px-3 py-2 text-xs font-semibold text-white">Credit wallet</button><button @click="impersonate(user)" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700" x-text="user.role?.role_name==='Admin'?'Login as affiliate admin':'Login as user'"></button></div></td></tr></template>
            <template x-if="!loading && users.length===0"><tr><td colspan="8" class="p-12 text-center text-slate-400">No matching users found.</td></tr></template>
        </tbody></table></div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4 text-sm"><p class="text-slate-500">Page <span class="font-semibold text-slate-700" x-text="page"></span> of <span class="font-semibold text-slate-700" x-text="lastPage"></span> · <span x-text="userTotal"></span> users</p><div class="flex gap-2"><button @click="changePage(page-1)" :disabled="page<=1||loading" class="rounded-lg border border-slate-200 px-3 py-2 font-semibold disabled:opacity-40">← Previous</button><button @click="changePage(page+1)" :disabled="page>=lastPage||loading" class="rounded-lg border border-slate-200 px-3 py-2 font-semibold disabled:opacity-40">Next →</button></div></div>
    </section>

    <section x-show="tab==='plans'">
        <div x-show="!selectedAffiliate" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Select one affiliate to edit or generate its user plans.</div>
        <div x-show="selectedAffiliate" class="mb-4 flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4"><p class="text-sm text-slate-600"><span class="font-semibold" x-text="plans.length"></span> affiliate plans from <span class="font-semibold" x-text="sourcePlanCount"></span> global user plans.</p><button @click="generateUserPlans()" class="rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-white">Generate missing user plans</button></div>
        <div x-show="selectedAffiliate" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <template x-for="plan in plans" :key="plan.id"><form @submit.prevent="savePlan(plan)" class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-start justify-between"><div><h3 class="font-semibold" x-text="plan.user_plan_name"></h3><p class="text-xs text-slate-400" x-text="'Pricing level '+plan.plan_level"></p></div><span class="rounded-full bg-slate-100 px-2 py-1 text-xs" x-text="number(plan.users_count)+' users'"></span></div>
            <label class="mt-4 block text-sm">Affiliate display name<input x-model="plan.updated_user_plan_name" :placeholder="plan.user_plan_name" class="mt-1 w-full rounded-xl border-slate-200"></label>
            <label class="mt-3 block text-sm">Maximum profit<input x-model="plan.max_profit" type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-200"></label>
            <div class="mt-4 space-y-2 text-sm"><label class="block"><input x-model="plan.visibility" true-value="1" false-value="0" type="checkbox"> Available for assignment</label><label class="block"><input x-model="plan.is_default" true-value="1" false-value="0" type="checkbox"> Default plan for new users</label></div>
            <button class="mt-5 w-full rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white">Save user plan</button>
        </form></template>
        <template x-if="!loading && plans.length===0"><div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white py-14 text-center text-slate-400">This affiliate has no user plans configured.</div></template>
        </div>
    </section>

    <div x-cloak x-show="showModal" class="fixed inset-0 z-[80] grid place-items-center bg-slate-950/60 p-4" @keydown.escape.window="showModal=false">
        <form @submit.prevent="saveUser()" class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex justify-between"><div><h2 class="text-lg font-bold" x-text="editingUser?'Edit affiliate user':'Add affiliate user'"></h2><p class="text-sm text-slate-500">Identity, access and affiliate pricing-plan assignment.</p></div><button type="button" @click="showModal=false" class="text-xl text-slate-400">×</button></div>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <label class="text-sm">First name<input x-model="form.first_name" required class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-sm">Last name<input x-model="form.last_name" required class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-sm">Other names<input x-model="form.other_names" class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-sm">Username<input x-model="form.username" required class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-sm">Email<input x-model="form.email" type="email" required class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-sm">Phone<input x-model="form.phone_number" class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-sm">Affiliate user plan<select x-model="form.user_plan_id" class="mt-1 w-full rounded-xl border-slate-200"><option value="">No plan</option><template x-for="plan in plansForEditing()" :key="plan.id"><option :value="plan.id" x-text="`${planName(plan)} (Level ${plan.plan_level})`"></option></template></select></label>
                <label class="text-sm">Role<select x-model="form.role_id" class="mt-1 w-full rounded-xl border-slate-200"><template x-for="role in roles" :key="role.id"><option :value="role.id" x-text="role.role_name"></option></template></select></label>
                <label class="text-sm">Customer category<select x-model="form.customer_category" class="mt-1 w-full rounded-xl border-slate-200"><option value="generic">Generic</option><option value="pos">POS</option></select></label>
                <label class="text-sm">Account tier<input x-model="form.account_tier" type="number" min="0" max="5" class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-sm sm:col-span-2">Customer landmark<input x-model="form.customer_landmark" class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-sm">Default wallet<select x-model="form.default_wallet_setting" class="mt-1 w-full rounded-xl border-slate-200"><option value="main_wallet">Main wallet</option><option value="bulk_data_wallet">Bulk data wallet</option></select></label>
                <label class="text-sm"><span x-text="editingUser?'New password (leave blank to retain current password)':'Password'"></span><input x-model="form.password" type="password" :required="!editingUser" minlength="8" class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label x-show="!editingUser" class="text-sm">Confirm password<input x-model="form.password_confirmation" type="password" :required="!editingUser" minlength="8" class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-sm" :class="editingUser?'sm:col-span-2':''"><span x-text="editingUser?'New transaction PIN (leave blank to retain current PIN)':'Transaction PIN'"></span><input x-model="form.pin" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" :required="!editingUser" placeholder="Exactly 6 digits" class="mt-1 w-full rounded-xl border-slate-200"></label>
            </div>
            <div class="mt-5 flex flex-wrap gap-5 rounded-xl bg-slate-50 p-4 text-sm"><label><input x-model="form.active" true-value="1" false-value="0" type="checkbox"> Account active</label><label><input x-model="form.email_verified" type="checkbox"> Email verified</label></div>
            <div class="mt-6 flex justify-end gap-3"><button type="button" @click="showModal=false" class="px-4 py-2 text-sm font-semibold text-slate-500">Cancel</button><button class="rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-white" x-text="editingUser?'Save user changes':'Create user'"></button></div>
        </form>
    </div>
    <div x-cloak x-show="showCreditModal" class="fixed inset-0 z-[90] grid place-items-center bg-slate-950/60 p-4" @keydown.escape.window="showCreditModal=false">
        <form @submit.prevent="creditWallet()" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex justify-between"><div><h2 class="text-lg font-bold">Credit user wallet</h2><p class="text-sm text-slate-500" x-text="creditTarget?`${creditTarget.first_name} ${creditTarget.last_name} · ${creditTarget.affiliate?.name}`:''"></p></div><button type="button" @click="showCreditModal=false" class="text-xl text-slate-400">×</button></div>
            <div class="mt-4 rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Current main wallet</p><p class="mt-1 text-xl font-bold" x-text="money(creditTarget?.main_wallet)"></p></div>
            <label class="mt-4 block text-sm font-medium">Credit amount<input x-model="creditForm.amount" type="number" min="1" step="0.01" required class="mt-1 w-full rounded-xl border-slate-200"></label>
            <label class="mt-4 block text-sm font-medium">Reason / audit note<textarea x-model="creditForm.reason" minlength="5" required class="mt-1 w-full rounded-xl border-slate-200"></textarea></label>
            <div class="mt-6 flex justify-end gap-3"><button type="button" @click="showCreditModal=false" class="px-4 py-2 text-sm font-semibold text-slate-500">Cancel</button><button class="rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-white">Confirm wallet credit</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init',()=>Alpine.data('affiliateUsers',()=>({
    selectedAffiliate:@js((string) request('affiliate_id','')),tab:'users',search:'',users:[],userTotal:0,page:1,lastPage:1,plans:[],roles:[],sourcePlanCount:0,loading:false,notice:'',error:'',showModal:false,editingUser:null,form:{},showCreditModal:false,creditTarget:null,creditForm:{amount:'',reason:''},base:@js(url('/admin/affiliates')),allDataUrl:@js(route('platform-admin.affiliate-users.all-data')),
    async request(method,url,data=null){this.error='';try{return await axios({method,url,data})}catch(e){this.error=Object.values(e.response?.data?.errors||{}).flat()[0]||e.response?.data?.message||'Unable to complete request.';throw e}},
    async load(){this.loading=true;try{const params=new URLSearchParams({search:this.search,affiliate_id:this.selectedAffiliate,page:this.page});const {data}=await this.request('get',`${this.allDataUrl}?${params}`);this.users=data.users.data;this.userTotal=data.users.total;this.page=data.users.current_page;this.lastPage=data.users.last_page;this.plans=data.plans;this.roles=data.roles;this.sourcePlanCount=data.source_plan_count}finally{this.loading=false}},
    async changePage(page){if(page<1||page>this.lastPage)return;this.page=page;await this.load()},
    blank(){return{first_name:'',last_name:'',other_names:'',username:'',email:'',phone_number:'',pin:'',password:'',password_confirmation:'',user_plan_id:this.plans.find(p=>Number(p.is_default)===1)?.id||'',role_id:this.roles.find(r=>r.role_name==='User')?.id||this.roles[0]?.id||'',customer_category:'generic',customer_landmark:'',account_tier:0,default_wallet_setting:'main_wallet',active:'1',email_verified:true}},
    openCreate(){this.editingUser=null;this.form=this.blank();this.showModal=true},
    openEdit(user){this.editingUser=user;this.form={first_name:user.first_name,last_name:user.last_name,other_names:user.other_names||'',username:user.username,email:user.email,phone_number:user.phone_number||'',password:'',pin:'',user_plan_id:user.user_plan_id||'',role_id:user.role_id,active:String(user.active),email_verified:!!user.email_verified_at,customer_category:user.customer_category||'generic',customer_landmark:user.customer_landmark||'',account_tier:user.account_tier||0,default_wallet_setting:user.default_wallet_setting||'main_wallet'};this.showModal=true},
    async saveUser(){if(this.editingUser){const affiliateId=this.editingUser.affiliate_id;const {data}=await this.request('patch',`${this.base}/${affiliateId}/management-users/${this.editingUser.id}`,this.form);Object.assign(this.editingUser,data.user);this.flash(data.message)}else{const role=this.roles.find(r=>String(r.id)===String(this.form.role_id));const payload={...this.form,role:role?.role_name||'User'};delete payload.role_id;const {data}=await this.request('post',`${this.base}/${this.selectedAffiliate}/users`,payload);this.flash(data.message);await this.load()}this.showModal=false},
    async savePlan(plan){const {data}=await this.request('patch',`${this.base}/${this.selectedAffiliate}/management-user-plans/${plan.id}`,plan);this.flash(data.message);if(Number(plan.is_default)===1)this.plans.forEach(p=>{if(p.id!==plan.id)p.is_default='0'})},
    openCredit(user){this.creditTarget=user;this.creditForm={amount:'',reason:''};this.showCreditModal=true},
    async creditWallet(){const {data}=await this.request('post',`${this.base}/${this.creditTarget.affiliate_id}/users/${this.creditTarget.id}/credit`,this.creditForm);this.creditTarget.main_wallet=data.user.main_wallet;this.showCreditModal=false;this.flash(data.message)},
    async impersonate(user){if(!confirm(`Open a new session as ${user.first_name} ${user.last_name}?`))return;const popup=window.open('about:blank','_blank');try{const {data}=await this.request('post',`${this.base}/${user.affiliate_id}/management-users/${user.id}/impersonate`);if(popup)popup.location=data.url;else window.location=data.url;this.flash(data.message)}catch(e){if(popup)popup.close()}},
    async generateUserPlans(){const {data}=await this.request('post',`${this.base}/${this.selectedAffiliate}/management-user-plans/generate`);this.flash(data.message);await this.load()},
    plansForEditing(){const affiliateId=this.editingUser?.affiliate_id||this.selectedAffiliate;return this.plans.filter(p=>String(p.affiliate_id)===String(affiliateId))},
    planName(plan){return plan?.updated_user_plan_name||plan?.user_plan_name||'No plan'},flash(message){this.notice=message;setTimeout(()=>this.notice='',3500)},money(v){return new Intl.NumberFormat('en-NG',{style:'currency',currency:'NGN'}).format(Number(v||0))},number(v){return new Intl.NumberFormat('en-NG').format(Number(v||0))}
})));
</script>
@endpush
