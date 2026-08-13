@extends('platform-admin.layouts.app')

@section('title', 'Parent businesses')
@section('heading', 'Parent businesses')

@section('content')
<div x-data="parentBusinesses()" x-init="load()" class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div><h2 class="text-2xl font-bold tracking-tight">Parent business directory</h2><p class="mt-1 max-w-2xl text-sm text-slate-500">Create website owners before they configure providers, plans and affiliates.</p></div>
        <button type="button" @click="openForm()" class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">Create parent business</button>
    </div>

    <div x-show="notice" x-text="notice" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800"></div>
    <div x-show="error" x-text="error" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"></div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <template x-for="card in summaryCards" :key="card.label"><div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wider text-slate-400" x-text="card.label"></p><p class="mt-2 text-3xl font-bold" x-text="card.value"></p></div></template>
    </div>

    <section x-show="showForm" x-transition class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm lg:p-7">
        <div class="flex items-start justify-between gap-4"><div><h3 class="text-lg font-semibold">Create parent business</h3><p class="mt-1 text-sm text-slate-500">The first administrator and six reseller levels will be created automatically.</p></div><button type="button" @click="showForm=false" class="text-sm font-semibold text-slate-500">Close</button></div>
        <form @submit.prevent="save" class="mt-6 space-y-6">
            <div><h4 class="text-sm font-bold uppercase tracking-wider text-slate-400">Business information</h4><div class="mt-3 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <label class="text-sm font-medium">Business name<input x-model="form.business.name" @input="syncSlug" required class="mt-1 w-full rounded-xl border-slate-200" placeholder="Paultechs"></label>
                <label class="text-sm font-medium">Slug<input x-model="form.business.slug" @input="slugTouched=true" required pattern="[a-z0-9_-]+" class="mt-1 w-full rounded-xl border-slate-200" placeholder="paultechs"></label>
                <label class="text-sm font-medium">Status<select x-model="form.business.status" class="mt-1 w-full rounded-xl border-slate-200"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
                <label class="text-sm font-medium">Contact email<input x-model="form.business.contact_email" type="email" class="mt-1 w-full rounded-xl border-slate-200" placeholder="support@example.com"></label>
                <label class="text-sm font-medium">Contact phone<input x-model="form.business.contact_phone" class="mt-1 w-full rounded-xl border-slate-200" placeholder="2348030000000"></label>
            </div></div>
            <div class="border-t border-slate-100 pt-6"><h4 class="text-sm font-bold uppercase tracking-wider text-slate-400">First parent administrator</h4><div class="mt-3 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <label class="text-sm font-medium">Administrator name<input x-model="form.admin.name" required class="mt-1 w-full rounded-xl border-slate-200" placeholder="Business owner"></label>
                <label class="text-sm font-medium">Login email<input x-model="form.admin.email" type="email" required class="mt-1 w-full rounded-xl border-slate-200" placeholder="owner@example.com"></label>
                <label class="text-sm font-medium">Temporary password<div class="relative mt-1"><input x-model="form.admin.password" :type="showPassword?'text':'password'" required minlength="12" autocomplete="new-password" class="w-full rounded-xl border-slate-200 pr-16" placeholder="At least 12 characters"><button type="button" @click="showPassword=!showPassword" class="absolute inset-y-0 right-3 text-xs font-semibold text-slate-500" x-text="showPassword?'Hide':'Show'"></button></div></label>
            </div><div class="mt-4 flex flex-wrap gap-5"><label class="flex items-center gap-2 text-sm"><input x-model="form.admin.active" type="checkbox"> Administrator active</label><label class="flex items-center gap-2 text-sm"><input x-model="form.admin.must_change_password" type="checkbox"> Require password change on first login</label></div></div>
            <div class="flex justify-end gap-3"><button type="button" @click="showForm=false" class="rounded-xl border border-slate-200 px-5 py-3 font-semibold">Cancel</button><button :disabled="saving" class="rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white disabled:opacity-50" x-text="saving?'Creating…':'Create parent and administrator'"></button></div>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold">Open a parent workspace</h3><p class="mt-1 text-sm text-slate-500">Choose a parent below to enter its dashboard as the first active parent administrator.</p><div class="mt-3 flex flex-wrap gap-2"><template x-for="parent in parents" :key="`imp-${parent.id}`"><form method="POST" :action="`{{ url('/admin/parent-businesses') }}/${parent.id}/impersonate`">@csrf<button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white" x-text="parent.name"></button></form></template></div></section>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="font-semibold">All parent businesses</h3></div>
        <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3"><template x-for="parent in parents" :key="parent.id"><article class="rounded-2xl border border-slate-200 p-5"><div class="flex items-start justify-between gap-3"><div><h4 class="font-semibold" x-text="parent.name"></h4><p class="text-xs text-slate-400" x-text="parent.slug"></p></div><span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase" :class="parent.status==='active'?'bg-emerald-100 text-emerald-700':'bg-slate-100 text-slate-600'" x-text="parent.status"></span></div><div class="mt-4 space-y-1 text-sm text-slate-500"><p x-text="parent.contact_email||'No contact email'"></p><p x-text="parent.contact_phone||'No contact phone'"></p></div><div class="mt-4 rounded-xl bg-slate-50 p-3"><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Parent administrator</p><p class="mt-1 text-sm font-medium" x-text="parent.admins[0]?.name||'No administrator'"></p><p class="text-xs text-slate-500" x-text="parent.admins[0]?.email||''"></p></div><div class="mt-4 grid grid-cols-3 gap-2 text-center"><div class="rounded-lg bg-slate-50 p-2"><p class="font-bold" x-text="parent.affiliate_count"></p><p class="text-[10px] text-slate-400">Affiliates</p></div><div class="rounded-lg bg-slate-50 p-2"><p class="font-bold" x-text="parent.provider_connection_count"></p><p class="text-[10px] text-slate-400">Providers</p></div><div class="rounded-lg bg-slate-50 p-2"><p class="font-bold" x-text="parent.level_count"></p><p class="text-[10px] text-slate-400">Levels</p></div></div></article></template><p x-show="!loading&&parents.length===0" class="text-sm text-slate-500">No parent businesses have been created.</p></div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init',()=>Alpine.data('parentBusinesses',()=>({
    parents:[],summary:{total:0,active:0,affiliates:0,connections:0},showForm:false,showPassword:false,saving:false,loading:true,notice:'',error:'',slugTouched:false,form:{},
    urls:{data:@js(route('platform-admin.parent-businesses.data')),store:@js(route('platform-admin.parent-businesses.store'))},
    get summaryCards(){return [{label:'Parent businesses',value:this.summary.total},{label:'Active parents',value:this.summary.active},{label:'Affiliates',value:this.summary.affiliates},{label:'Provider connections',value:this.summary.connections}]},
    blank(){return {business:{name:'',slug:'',contact_email:'',contact_phone:'',status:'active'},admin:{name:'',email:'',password:'',active:true,must_change_password:true}}},
    async load(){this.loading=true;try{const {data}=await axios.get(this.urls.data);this.parents=data.parents;this.summary=data.summary}catch(e){this.fail(e)}finally{this.loading=false}},
    openForm(){this.form=this.blank();this.slugTouched=false;this.showPassword=false;this.showForm=true;this.$nextTick(()=>document.querySelector('[x-model="form.business.name"]')?.focus())},
    syncSlug(){if(!this.slugTouched)this.form.business.slug=this.form.business.name.toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')},
    async save(){this.saving=true;this.error='';try{const {data}=await axios.post(this.urls.store,this.form);this.notice=data.message;this.showForm=false;this.form=this.blank();await this.load();setTimeout(()=>this.notice='',4000)}catch(e){this.fail(e)}finally{this.saving=false}},
    fail(e){this.error=Object.values(e.response?.data?.errors||{}).flat()[0]||e.response?.data?.message||'Unable to complete this action.'}
})))
</script>
@endpush
