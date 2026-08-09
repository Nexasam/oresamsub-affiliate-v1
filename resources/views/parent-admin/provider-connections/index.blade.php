@extends('parent-admin.layouts.app')

@section('title', 'Provider connections')
@section('heading', 'Provider connections')

@section('content')
<div x-data="providerConnections()" x-init="load()" class="space-y-5">
    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">Configure how this parent communicates with providers. Credentials are encrypted and never displayed again after saving. Live purchases are not switched by this screen.</div>
    <div x-show="notice" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800" x-text="notice"></div>
    <div x-show="error" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700" x-text="error"></div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-semibold">Configured connections</h2><p class="text-sm text-slate-500">One connection can be primary. Other connections remain ready for later backup routing.</p></div><button @click="newConnection" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Add provider connection</button></div>
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3"><template x-for="connection in connections" :key="connection.id"><button @click="edit(connection)" class="rounded-xl border border-slate-200 p-4 text-left hover:border-blue-300"><div class="flex items-center justify-between gap-2"><p class="font-semibold" x-text="connection.name"></p><span x-show="connection.settings?.is_primary" class="rounded-full bg-blue-100 px-2 py-1 text-[10px] font-bold text-blue-700">PRIMARY</span></div><p class="mt-1 text-xs text-slate-500" x-text="connection.provider_connection?.name"></p><p class="mt-3 truncate text-xs text-slate-400" x-text="connection.base_url||'Service endpoints only'"></p></button></template><p x-show="connections.length===0" class="text-sm text-slate-500">No provider connection has been configured.</p></div>
    </section>

    <form x-show="editing" @submit.prevent="save" class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4"><h2 class="font-semibold">Provider information</h2><p class="text-sm text-slate-500">Select a platform-approved adapter and name this parent connection.</p></div>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4"><label class="text-sm">Approved adapter<select x-model="form.provider_connection_id" required class="mt-1 w-full rounded-xl border-slate-200"><option value="">Select adapter</option><template x-for="adapter in adapters" :key="adapter.id"><option :value="adapter.id" x-text="`${adapter.name} · ${adapter.adapter}`"></option></template></select></label><label class="text-sm">Connection name<input x-model="form.name" required class="mt-1 w-full rounded-xl border-slate-200" placeholder="Affatech Primary"></label><label class="text-sm">Base URL<input x-model="form.base_url" type="url" class="mt-1 w-full rounded-xl border-slate-200" placeholder="https://provider.example/api"></label><label class="text-sm">Status<select x-model="form.status" class="mt-1 w-full rounded-xl border-slate-200"><option value="active">Active</option><option value="inactive">Inactive</option></select></label></div>
            <label class="mt-4 flex items-center gap-2 text-sm font-medium"><input x-model="form.is_primary" type="checkbox"> Make this the primary provider connection</label>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Encrypted credentials</h2><p class="mt-1 text-sm text-slate-500">Leave a field blank while editing to preserve its saved value.</p>
            <div class="mt-4 grid gap-3 md:grid-cols-3"><template x-for="field in credentialFields" :key="field"><label class="text-sm capitalize"><span x-text="field.replaceAll('_',' ')"></span><input x-model="form.credentials[field]" type="password" autocomplete="new-password" class="mt-1 w-full rounded-xl border-slate-200" :placeholder="form.credential_status[field]?'Saved · enter replacement':'Enter value'"></label></template></div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Service endpoints</h2><div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4"><template x-for="service in ['data','airtime','cable','electricity']" :key="service"><label class="text-sm capitalize"><span x-text="`${service} URL`"></span><input x-model="form.settings.endpoints[service]" type="url" class="mt-1 w-full rounded-xl border-slate-200" :placeholder="`https://provider.example/api/${service}`"></label></template></div>
            <div class="mt-4 grid gap-3 md:grid-cols-2"><label class="text-sm">HTTP method<select x-model="form.settings.http_method" class="mt-1 w-full rounded-xl border-slate-200"><option>POST</option><option>GET</option></select></label><label class="text-sm">Timeout in seconds<input x-model="form.settings.timeout_seconds" type="number" min="5" max="120" class="mt-1 w-full rounded-xl border-slate-200"></label></div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between"><div><h2 class="font-semibold">Request mapping</h2><p class="text-sm text-slate-500">Map provider keys to runtime values, credentials or literals.</p></div><button type="button" @click="addRow('request_parameters')" class="rounded-lg border px-3 py-2 text-xs font-semibold">Add mapping</button></div>
            <div class="mt-4 space-y-2"><template x-for="(row,index) in form.settings.request_parameters" :key="row.id"><div class="grid gap-2 md:grid-cols-[1fr_160px_1fr_auto]"><input x-model="row.key" required placeholder="Provider key" class="rounded-lg border-slate-200"><select x-model="row.type" class="rounded-lg border-slate-200"><option value="runtime">Runtime</option><option value="credential">Credential</option><option value="literal">Literal</option></select><select x-show="row.type==='runtime'" x-model="row.value" class="rounded-lg border-slate-200"><template x-for="field in runtimeFields"><option :value="field" x-text="field"></option></template></select><select x-show="row.type==='credential'" x-model="row.value" class="rounded-lg border-slate-200"><template x-for="field in credentialFields"><option :value="field" x-text="field"></option></template></select><input x-show="row.type==='literal'" x-model="row.value" placeholder="Static value" class="rounded-lg border-slate-200"><button type="button" @click="removeRow('request_parameters',index)" class="text-sm font-semibold text-red-600">Remove</button></div></template></div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between"><div><h2 class="font-semibold">Request headers</h2><p class="text-sm text-slate-500">Use credential placeholders for secret header values.</p></div><button type="button" @click="addRow('request_headers')" class="rounded-lg border px-3 py-2 text-xs font-semibold">Add header</button></div>
            <div class="mt-4 space-y-2"><template x-for="(row,index) in form.settings.request_headers" :key="row.id"><div class="grid gap-2 md:grid-cols-[1fr_160px_1fr_auto]"><input x-model="row.key" required placeholder="Authorization" class="rounded-lg border-slate-200"><select x-model="row.type" class="rounded-lg border-slate-200"><option value="credential">Credential</option><option value="runtime">Runtime</option><option value="literal">Literal</option></select><select x-show="row.type==='credential'" x-model="row.value" class="rounded-lg border-slate-200"><template x-for="field in credentialFields"><option :value="field" x-text="field"></option></template></select><select x-show="row.type==='runtime'" x-model="row.value" class="rounded-lg border-slate-200"><template x-for="field in runtimeFields"><option :value="field" x-text="field"></option></template></select><input x-show="row.type==='literal'" x-model="row.value" placeholder="Static value" class="rounded-lg border-slate-200"><button type="button" @click="removeRow('request_headers',index)" class="text-sm font-semibold text-red-600">Remove</button></div></template></div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Network mapping</h2><div class="mt-4 grid gap-3 md:grid-cols-4"><template x-for="network in ['MTN','GLO','AIRTEL','9MOBILE']" :key="network"><label class="text-sm"><span x-text="network"></span><input x-model="form.settings.network_mapping[network]" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Provider network ID"></label></template></div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between"><div><h2 class="font-semibold">Success conditions</h2><p class="text-sm text-slate-500">Every configured response condition must match.</p></div><button type="button" @click="addCondition" class="rounded-lg border px-3 py-2 text-xs font-semibold">Add condition</button></div><div class="mt-4 space-y-2"><template x-for="(row,index) in form.settings.success_conditions" :key="row.id"><div class="grid gap-2 md:grid-cols-[1fr_1fr_auto]"><input x-model="row.key" required placeholder="status or data.completed" class="rounded-lg border-slate-200"><input x-model="row.value" required placeholder="success" class="rounded-lg border-slate-200"><button type="button" @click="form.settings.success_conditions.splice(index,1)" class="text-sm font-semibold text-red-600">Remove</button></div></template></div><div class="mt-4 grid gap-3 md:grid-cols-2"><label class="text-sm">Success message path<input x-model="form.settings.success_message_path" required class="mt-1 w-full rounded-xl border-slate-200" placeholder="data.message"></label><label class="text-sm">Failure message path<input x-model="form.settings.failure_message_path" required class="mt-1 w-full rounded-xl border-slate-200" placeholder="error.message"></label></div>
        </section>

        <div class="flex justify-end gap-3"><button type="button" @click="editing=false" class="rounded-xl border border-slate-200 px-5 py-3 font-semibold">Cancel</button><button :disabled="saving" class="rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white disabled:opacity-50" x-text="saving?'Saving…':'Save provider connection'"></button></div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init',()=>Alpine.data('providerConnections',()=>({
    connections:[],adapters:[],runtimeFields:[],credentialFields:[],editing:false,saving:false,notice:'',error:'',form:{},
    urls:{data:@js(route('parent-admin.provider-connections.data')),store:@js(route('parent-admin.provider-connections.store')),base:@js(url('/parent-admin/provider-connections'))},
    blank(){return {id:null,provider_connection_id:'',name:'',base_url:'',status:'active',is_primary:false,credential_status:{},credentials:{api_public_key:'',api_secret_key:'',api_password:''},settings:{http_method:'POST',timeout_seconds:30,endpoints:{data:'',airtime:'',cable:'',electricity:''},request_parameters:[{id:crypto.randomUUID(),key:'',type:'runtime',value:'phone_number'}],request_headers:[],network_mapping:{MTN:'',GLO:'',AIRTEL:'','9MOBILE':''},success_conditions:[{id:crypto.randomUUID(),key:'status',value:'success'}],success_message_path:'data.message',failure_message_path:'error.message',expected_success_code:200,expected_failure_code:null,bank_name:'',bank_accounts:'',support_url:''}}},
    async load(){try{const {data}=await axios.get(this.urls.data);this.connections=data.connections;this.adapters=data.adapters;this.runtimeFields=data.runtime_fields;this.credentialFields=data.credential_fields}catch(e){this.fail(e)}},
    newConnection(){this.form=this.blank();this.editing=true;window.scrollTo({top:document.body.scrollHeight,behavior:'smooth'})},
    edit(connection){this.form=this.blank();Object.assign(this.form,{id:connection.id,provider_connection_id:connection.provider_connection_id,name:connection.name,base_url:connection.base_url||'',status:connection.status,is_primary:!!connection.settings?.is_primary,credential_status:connection.credential_status});this.form.settings={...this.form.settings,...structuredClone(connection.settings||{}),endpoints:{...this.form.settings.endpoints,...(connection.settings?.endpoints||{})},network_mapping:{...this.form.settings.network_mapping,...(connection.settings?.network_mapping||{})}};['request_parameters','request_headers','success_conditions'].forEach(key=>this.form.settings[key]=(this.form.settings[key]||[]).map(row=>({...row,id:crypto.randomUUID()})));this.editing=true},
    addRow(key){this.form.settings[key].push({id:crypto.randomUUID(),key:'',type:key==='request_headers'?'credential':'runtime',value:key==='request_headers'?'api_public_key':'phone_number'})},removeRow(key,index){this.form.settings[key].splice(index,1)},addCondition(){this.form.settings.success_conditions.push({id:crypto.randomUUID(),key:'',value:''})},
    payload(){const copy=structuredClone(this.form);delete copy.id;delete copy.credential_status;['request_parameters','request_headers','success_conditions'].forEach(key=>copy.settings[key]=copy.settings[key].map(({id,...row})=>row));return copy},
    async save(){this.saving=true;this.error='';try{const method=this.form.id?'put':'post',url=this.form.id?`${this.urls.base}/${this.form.id}`:this.urls.store;const {data}=await axios({method,url,data:this.payload()});this.show(data.message);this.editing=false;await this.load()}catch(e){this.fail(e)}finally{this.saving=false}},
    show(message){this.notice=message;setTimeout(()=>this.notice='',4000)},fail(e){this.error=Object.values(e.response?.data?.errors||{}).flat()[0]||e.response?.data?.message||'Unable to complete this action.'}
})));
</script>
@endpush
