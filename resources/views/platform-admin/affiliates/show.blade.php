@extends('platform-admin.layouts.app')

@section('title', $affiliate->name)
@section('eyebrow', 'Affiliate workspace')
@section('heading', $affiliate->name)
@section('content')
<div
    x-data="affiliateWorkspace({
        usersUrl: @js(route('platform-admin.affiliates.users', $affiliate)),
        transactionsUrl: @js(route('platform-admin.affiliates.transactions', $affiliate)),
        bankCodesUrl: @js(route('platform-admin.affiliates.bank-codes', $affiliate)),
        updateAffiliateUrl: @js(route('platform-admin.affiliates.update', $affiliate)),
        fundingOptionsUrl: @js(route('platform-admin.affiliates.funding-options', $affiliate)),
        userUrlBase: @js(url('/admin/affiliates/'.$affiliate->id.'/users')),
        bankCodeUrlBase: @js(url('/admin/affiliates/'.$affiliate->id.'/bank-codes')),
        fundingOptionUrlBase: @js(url('/admin/affiliates/'.$affiliate->id.'/funding-options'))
    })"
    x-init="loadFundingOptions(); loadBankCodes()"
>
    <div x-show="loading" x-transition class="mb-4 flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800"><span class="h-4 w-4 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></span> Loading affiliate records…</div>
    <div class="mb-6 flex flex-col gap-4 rounded-2xl bg-slate-950 p-6 text-white sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <div class="grid h-14 w-14 place-items-center rounded-2xl bg-emerald-400 text-xl font-black text-slate-950">{{ strtoupper(substr($affiliate->name, 0, 1)) }}</div>
            <div><h2 class="text-xl font-bold">{{ $affiliate->name }}</h2><p class="mt-1 text-sm text-slate-400">{{ $affiliate->contact_email }} · {{ $affiliate->slug }}</p></div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('platform-admin.operations.index', ['affiliate_id' => $affiliate->id]) }}" class="rounded-xl bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-slate-950 hover:bg-emerald-300">Operations</a>
            <button @click="toggleAffiliate()" :disabled="busy" class="rounded-xl px-4 py-2.5 text-sm font-semibold {{ $affiliate->activation_status == 1 ? 'bg-red-400/10 text-red-300 hover:bg-red-400/20' : 'bg-emerald-400 text-slate-950 hover:bg-emerald-300' }}">
                {{ $affiliate->activation_status == 1 ? 'Deactivate affiliate' : 'Activate affiliate' }}
            </button>
        </div>
    </div>

    <div x-show="notice" x-transition class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" x-text="notice"></div>
    <div x-show="error" x-transition class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" x-text="error"></div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Users</p><p class="mt-2 text-2xl font-bold">{{ number_format($affiliate->users_count) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Transactions</p><p class="mt-2 text-2xl font-bold">{{ number_format($affiliate->transactions_count) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Platform status</p><p class="mt-2 text-lg font-bold {{ $affiliate->activation_status == 1 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $affiliate->activation_status == 1 ? 'Active' : 'Inactive' }}</p></div>
    </div>

    <div class="mb-5 flex gap-2 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2">
        <template x-for="item in tabs" :key="item.id">
            <button @click="selectTab(item.id)" :class="tab === item.id ? 'bg-slate-950 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-50'" class="whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-semibold" x-text="item.label"></button>
        </template>
    </div>

    <section x-show="tab === 'users'" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div><h3 class="font-semibold">Affiliate users</h3><p class="text-sm text-slate-500">Search, activate, deactivate or change account roles.</p></div>
            <div class="flex gap-2">
                <input x-model="userSearch" @input.debounce.400ms="loadUsers()" placeholder="Search users…" class="min-w-0 rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <button @click="showUserModal = true" class="whitespace-nowrap rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600">+ Add user</button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4">User</th><th class="px-5 py-4">Role</th><th class="px-5 py-4">Wallet</th><th class="px-5 py-4">Status</th><th class="px-5 py-4 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-if="loading"><tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">Loading users…</td></tr></template>
                    <template x-for="user in users" :key="user.id">
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-4"><p class="font-semibold" x-text="`${user.first_name} ${user.last_name}`"></p><p class="text-xs text-slate-400" x-text="user.email"></p></td>
                            <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold" x-text="user.role?.role_name || 'Unknown'"></span></td>
                            <td class="px-5 py-4" x-text="money(user.main_wallet)"></td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="Number(user.active) === 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'" x-text="Number(user.active) === 1 ? 'Active' : 'Inactive'"></span></td>
                            <td class="px-5 py-4 text-right">
                                <button @click="updateUser(user, { active: Number(user.active) === 1 ? 0 : 1 })" class="mr-3 text-xs font-semibold text-slate-600 hover:text-emerald-600" x-text="Number(user.active) === 1 ? 'Deactivate' : 'Activate'"></button>
                                <button @click="updateUser(user, { role: user.role?.role_name === 'Admin' ? 'User' : 'Admin' })" class="text-xs font-semibold text-slate-600 hover:text-emerald-600" x-text="user.role?.role_name === 'Admin' ? 'Make user' : 'Make admin'"></button>
                                <button @click="openCustomer(user)" class="ml-3 text-xs font-semibold text-emerald-600">Details</button>
                                <button @click="creditTarget=user;showCreditModal=true" class="ml-3 text-xs font-semibold text-blue-600">Credit</button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && users.length === 0"><tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">No users found.</td></tr></template>
                </tbody>
            </table>
        </div>
    </section>

    <section x-show="tab === 'transactions'" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5"><h3 class="font-semibold">Transactions</h3><p class="text-sm text-slate-500">The latest activity across this affiliate.</p></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4">Reference</th><th class="px-5 py-4">User</th><th class="px-5 py-4">Category</th><th class="px-5 py-4">Amount</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Date</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="transaction in transactions" :key="transaction.id"><tr><td class="px-5 py-4 font-mono text-xs" x-text="transaction.txn_reference || `#${transaction.id}`"></td><td class="px-5 py-4" x-text="transaction.user ? `${transaction.user.first_name} ${transaction.user.last_name}` : '—'"></td><td class="px-5 py-4 capitalize" x-text="transaction.transaction_category || '—'"></td><td class="px-5 py-4 font-semibold" x-text="money(transaction.amount)"></td><td class="px-5 py-4" x-text="statusLabel(transaction.status)"></td><td class="px-5 py-4 text-slate-500" x-text="formatDate(transaction.created_at)"></td></tr></template>
                    <template x-if="!loading && transactions.length === 0"><tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No transactions found.</td></tr></template>
                </tbody>
            </table>
        </div>
    </section>

    <section x-show="tab === 'bank-codes'" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-5"><h3 class="font-semibold">Bank codes</h3><p class="text-sm text-slate-500">Update the codes used by this affiliate's funding options.</p></div>
        <form @submit.prevent="createBankCode()" class="mb-5 grid gap-3 rounded-xl bg-slate-50 p-4 md:grid-cols-5"><select x-model="newBankCode.funding_option_id" required class="rounded-xl border-slate-200 text-sm"><option value="">Funding option</option><template x-for="option in fundingOptions" :key="option.id"><option :value="option.id" x-text="option.funding_option_name"></option></template></select><input x-model="newBankCode.bank_name" placeholder="Bank name" class="rounded-xl border-slate-200 text-sm"><input x-model="newBankCode.bank_code" required placeholder="Bank code" class="rounded-xl border-slate-200 text-sm"><input x-model="newBankCode.bank_charges" type="number" min="0" step="0.01" placeholder="Charge" class="rounded-xl border-slate-200 text-sm"><button class="rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-white">Add bank code</button></form>
        <div class="grid gap-4 lg:grid-cols-2">
            <template x-for="code in bankCodes" :key="code.id">
                <form @submit.prevent="saveBankCode(code)" class="rounded-2xl border border-slate-200 p-4">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="text-sm"><span class="mb-1 block font-medium">Bank name</span><input x-model="code.bank_name" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500"></label>
                        <label class="text-sm"><span class="mb-1 block font-medium">Bank code</span><input x-model="code.bank_code" required class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500"></label>
                        <label class="text-sm"><span class="mb-1 block font-medium">Charge type</span><select x-model="code.rate_category" class="w-full rounded-xl border-slate-200 text-sm"><option>Flat</option><option>Percentage</option></select></label>
                        <label class="text-sm"><span class="mb-1 block font-medium">Charge</span><input x-model="code.bank_charges" type="number" min="0" step="0.01" class="w-full rounded-xl border-slate-200 text-sm"></label>
                        <label x-show="code.rate_category === 'Percentage'" class="text-sm"><span class="mb-1 block font-medium">Cap</span><input x-model="code.capped_at" type="number" min="0" step="0.01" class="w-full rounded-xl border-slate-200 text-sm"></label>
                    </div>
                    <div class="mt-4 flex items-center justify-between"><label class="flex items-center gap-2 text-sm"><input x-model="code.visibility_status" true-value="1" false-value="0" type="checkbox" class="rounded text-emerald-600 focus:ring-emerald-500"> Visible</label><button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Save changes</button></div>
                </form>
            </template>
            <template x-if="!loading && bankCodes.length === 0"><p class="col-span-full rounded-xl bg-slate-50 py-10 text-center text-sm text-slate-500">No bank codes are configured.</p></template>
        </div>
    </section>

    <section x-show="tab === 'settings'" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><form @submit.prevent="saveAffiliate()" class="grid gap-4 md:grid-cols-2"><template x-for="field in affiliateFields" :key="field.key"><label class="text-sm"><span x-text="field.label"></span><input x-model="affiliateForm[field.key]" :type="field.type || 'text'" class="mt-1 w-full rounded-xl border-slate-200"></label></template><label class="text-sm md:col-span-2">Parent/API key<input x-model="affiliateForm.parent_key" class="mt-1 w-full rounded-xl border-slate-200"></label><button class="rounded-xl bg-emerald-500 px-5 py-3 font-semibold text-white md:col-span-2">Save affiliate information</button></form></section>

    <section x-show="tab === 'funding'" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-5"><h3 class="font-semibold">Funding integrations</h3><p class="text-sm text-slate-500">Manage Securewave credentials and webhook configuration. Existing secrets stay masked.</p></div>
        <div class="space-y-4"><template x-for="option in fundingOptions" :key="option.id"><form @submit.prevent="saveFundingOption(option)" class="rounded-xl border border-slate-200 p-4"><div class="mb-4 flex justify-between"><div><p class="font-semibold" x-text="option.funding_option_name"></p><p class="text-xs text-slate-400 break-all" x-text="option.webhook_url || 'Webhook suffix not configured'"></p></div><label class="text-sm"><input type="checkbox" x-model="option.activation_status" true-value="1" false-value="0"> Active</label></div><div class="grid gap-3 md:grid-cols-3"><input x-model="option.api_public_key" :placeholder="option.api_public_key_masked || 'API/public key'" class="rounded-xl border-slate-200 text-sm"><input x-model="option.api_secret_key" :placeholder="option.api_secret_key_masked || 'Secret key'" class="rounded-xl border-slate-200 text-sm"><input x-model="option.contract_code" placeholder="Contract code" class="rounded-xl border-slate-200 text-sm"><input x-model="option.biz_bvn" placeholder="Business BVN" class="rounded-xl border-slate-200 text-sm"><input x-model="option.webhook_suffix_string" :placeholder="option.webhook_string?.webhook_suffix_string || 'Webhook suffix'" class="rounded-xl border-slate-200 text-sm"><button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Save integration</button></div></form></template></div>
    </section>

    <div x-cloak x-show="showUserModal" class="fixed inset-0 z-[60] grid place-items-center bg-slate-950/60 p-4" @keydown.escape.window="showUserModal = false">
        <form @submit.prevent="createUser()" @click.outside="showUserModal = false" class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between"><div><h3 class="text-lg font-bold">Add affiliate user</h3><p class="text-sm text-slate-500">Create an account directly inside {{ $affiliate->name }}.</p></div><button type="button" @click="showUserModal = false" class="text-xl text-slate-400">×</button></div>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <label class="text-sm"><span class="mb-1 block font-medium">First name</span><input x-model="newUser.first_name" required class="w-full rounded-xl border-slate-200"></label>
                <label class="text-sm"><span class="mb-1 block font-medium">Last name</span><input x-model="newUser.last_name" required class="w-full rounded-xl border-slate-200"></label>
                <label class="text-sm"><span class="mb-1 block font-medium">Username</span><input x-model="newUser.username" required class="w-full rounded-xl border-slate-200"></label>
                <label class="text-sm"><span class="mb-1 block font-medium">Phone</span><input x-model="newUser.phone_number" class="w-full rounded-xl border-slate-200"></label>
                <label class="text-sm sm:col-span-2"><span class="mb-1 block font-medium">Email</span><input x-model="newUser.email" type="email" required class="w-full rounded-xl border-slate-200"></label>
                <label class="text-sm"><span class="mb-1 block font-medium">Password</span><input x-model="newUser.password" type="password" minlength="8" required class="w-full rounded-xl border-slate-200"></label>
                <label class="text-sm"><span class="mb-1 block font-medium">Role</span><select x-model="newUser.role" class="w-full rounded-xl border-slate-200"><option>User</option><option>Admin</option></select></label>
            </div>
            <div class="mt-6 flex justify-end gap-3"><button type="button" @click="showUserModal = false" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600">Cancel</button><button :disabled="busy" class="rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Create user</button></div>
        </form>
    </div>
    <div x-cloak x-show="showCreditModal" class="fixed inset-0 z-[70] grid place-items-center bg-slate-950/60 p-4"><form @submit.prevent="creditUser()" class="w-full max-w-md rounded-2xl bg-white p-6"><h3 class="text-lg font-bold">Credit customer wallet</h3><p class="text-sm text-slate-500" x-text="creditTarget?.email"></p><input x-model="creditForm.amount" type="number" min="1" step="0.01" required placeholder="Amount" class="mt-5 w-full rounded-xl border-slate-200"><textarea x-model="creditForm.reason" minlength="5" required placeholder="Reason / audit note" class="mt-3 w-full rounded-xl border-slate-200"></textarea><div class="mt-4 flex justify-end gap-3"><button type="button" @click="showCreditModal=false">Cancel</button><button class="rounded-xl bg-emerald-500 px-4 py-2 font-semibold text-white">Confirm credit</button></div></form></div>
    <div x-cloak x-show="showCustomerModal" class="fixed inset-0 z-[70] grid place-items-center bg-slate-950/60 p-4"><div class="max-h-[85vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-6"><div class="flex justify-between"><h3 class="text-lg font-bold">Customer information</h3><button @click="showCustomerModal=false">×</button></div><template x-if="customer"><div class="mt-5 grid gap-3 sm:grid-cols-2"><template x-for="item in customerFacts()" :key="item[0]"><div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-400" x-text="item[0]"></p><p class="mt-1 text-sm font-semibold break-all" x-text="item[1] ?? '—'"></p></div></template></div></template></div></div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('affiliateWorkspace', (config) => ({
        ...config,
        tab: 'bank-codes', tabs: [{id:'bank-codes',label:'Bank codes'}, {id:'funding',label:'Funding & API'}, {id:'settings',label:'Affiliate details'}],
        users: [], transactions: [], bankCodes: [], fundingOptions: [], customer:null, creditTarget:null, loading: false, busy: false, notice: '', error: '', userSearch: '', showUserModal: false, showCreditModal:false, showCustomerModal:false,
        creditForm:{amount:'',reason:''},
        newBankCode:{funding_option_id:'',bank_name:'',bank_code:'',bank_charges:0,visibility_status:1,rate_category:'Flat',capped_at:100},
        affiliateForm:@js($affiliate->only(['name','contact_email','contact_phone','domain_url','parent_email','parent_key','address'])),
        affiliateFields:[{key:'name',label:'Name'},{key:'contact_email',label:'Contact email',type:'email'},{key:'contact_phone',label:'Contact phone'},{key:'domain_url',label:'Domain'},{key:'parent_email',label:'Parent email',type:'email'},{key:'address',label:'Address'}],
        newUser: { first_name:'', last_name:'', username:'', email:'', phone_number:'', password:'', role:'User' },
        async request(method, url, data = null) {
            this.error = '';
            try { return await axios({ method, url, data }); }
            catch (e) {
                this.error = Object.values(e.response?.data?.errors || {}).flat()[0] || e.response?.data?.message || 'Something went wrong.';
                throw e;
            }
        },
        async selectTab(tab) {
            this.tab = tab;
            if (tab === 'users') await this.loadUsers();
            if (tab === 'transactions') await this.loadTransactions();
            if (tab === 'bank-codes') { await this.loadFundingOptions(); await this.loadBankCodes(); }
            if (tab === 'funding') await this.loadFundingOptions();
        },
        async loadUsers() { this.loading = true; try { const {data} = await this.request('get', this.usersUrl + '?search=' + encodeURIComponent(this.userSearch)); this.users = data.data; } finally { this.loading = false; } },
        async loadTransactions() { this.loading = true; try { const {data} = await this.request('get', this.transactionsUrl); this.transactions = data.data; } finally { this.loading = false; } },
        async loadBankCodes() { this.loading = true; try { const {data} = await this.request('get', this.bankCodesUrl); this.bankCodes = data; } finally { this.loading = false; } },
        async loadFundingOptions(){this.loading=true;try{const {data}=await this.request('get',this.fundingOptionsUrl);this.fundingOptions=data.map(o=>({...o,api_public_key:'',api_secret_key:'',webhook_suffix_string:''}))}finally{this.loading=false}},
        async updateUser(user, changes) { this.busy = true; try { const {data} = await this.request('patch', `${this.userUrlBase}/${user.id}`, changes); Object.assign(user, data.user); this.flash(data.message); } finally { this.busy = false; } },
        async createUser() { this.busy = true; try { const {data} = await this.request('post', this.usersUrl, this.newUser); this.users.unshift(data.user); this.showUserModal = false; this.newUser = { first_name:'', last_name:'', username:'', email:'', phone_number:'', password:'', role:'User' }; this.flash(data.message); } finally { this.busy = false; } },
        async saveBankCode(code) { this.busy = true; try { const {data} = await this.request('patch', `${this.bankCodeUrlBase}/${code.id}`, code); this.flash(data.message); } finally { this.busy = false; } },
        async createBankCode(){this.busy=true;try{const {data}=await this.request('post',this.bankCodesUrl,this.newBankCode);this.bankCodes.unshift(data.bank_code);this.newBankCode={funding_option_id:'',bank_name:'',bank_code:'',bank_charges:0,visibility_status:1,rate_category:'Flat',capped_at:100};this.flash(data.message)}finally{this.busy=false}},
        async saveFundingOption(option){this.busy=true;try{const {data}=await this.request('patch',`${this.fundingOptionUrlBase}/${option.id}`,option);this.flash(data.message);await this.loadFundingOptions()}finally{this.busy=false}},
        async saveAffiliate(){this.busy=true;try{const {data}=await this.request('patch',this.updateAffiliateUrl,this.affiliateForm);this.flash(data.message)}finally{this.busy=false}},
        async openCustomer(user){const {data}=await this.request('get',`${this.userUrlBase}/${user.id}`);this.customer=data;this.showCustomerModal=true},
        async creditUser(){this.busy=true;try{const {data}=await this.request('post',`${this.userUrlBase}/${this.creditTarget.id}/credit`,this.creditForm);Object.assign(this.creditTarget,data.user);this.showCreditModal=false;this.creditForm={amount:'',reason:''};this.flash(data.message)}finally{this.busy=false}},
        customerFacts(){const u=this.customer;return [['Name',`${u.first_name} ${u.last_name}`],['Email',u.email],['Username',u.username],['Phone',u.phone_number],['Wallet',this.money(u.main_wallet)],['Role',u.role?.role_name],['Plan',u.user_plan?.plan_name],['Active',Number(u.active)===1?'Yes':'No'],['Email verified',u.email_verified_at?'Yes':'No'],['BVN verified',u.is_bvn_verified],['NIN verified',u.is_nin_verified],['Transactions',u.transactions_count],['Referrals',u.referrals_count],['Joined',this.formatDate(u.created_at)]]},
        async toggleAffiliate() { this.busy = true; try { await this.request('patch', this.updateAffiliateUrl, { activation_status: {{ $affiliate->activation_status == 1 ? 0 : 1 }} }); window.location.reload(); } finally { this.busy = false; } },
        flash(message) { this.notice = message; setTimeout(() => this.notice = '', 3500); },
        money(value) { return new Intl.NumberFormat('en-NG', { style:'currency', currency:'NGN' }).format(Number(value || 0)); },
        formatDate(value) { return value ? new Intl.DateTimeFormat('en-NG', { dateStyle:'medium', timeStyle:'short' }).format(new Date(value)) : '—'; },
        statusLabel(status) { return ({'1':'Successful','0':'Pending','-1':'Failed','2':'Refunded','3':'Processing'})[String(status)] || status; }
    }));
});
</script>
@endpush
