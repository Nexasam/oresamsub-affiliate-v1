@extends('parent-admin.layouts.app')

@section('title', 'Provider connections')
@section('heading', 'Provider connections')

@php
    $blankSettings = [
        'http_method' => 'POST', 'timeout_seconds' => 30, 'endpoints' => [],
        'request_parameters' => [['key' => 'phone_number', 'type' => 'runtime', 'value' => 'phone_number']],
        'request_headers' => [], 'network_mapping' => [],
        'success_conditions' => [['key' => 'status', 'value' => 'success']],
        'success_message_path' => 'data.message', 'failure_message_path' => 'error.message',
        'expected_success_code' => 200, 'expected_failure_code' => null, 'product_configs' => [],
    ];
    $saved = $editingConnection;
    $initialForm = old() ?: [
        'provider_connection_id' => $saved['provider_connection_id'] ?? '',
        'name' => $saved['name'] ?? '', 'base_url' => $saved['base_url'] ?? '',
        'status' => $saved['status'] ?? 'active', 'is_primary' => (bool) data_get($saved, 'settings.is_primary', false),
        'credentials' => array_fill_keys($credentialFields, ''),
        'settings' => array_replace_recursive($blankSettings, $saved['settings'] ?? []),
    ];
@endphp

@section('content')
<div class="space-y-5">
    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">Configure how this parent communicates with providers. Credentials are encrypted and never displayed after saving. New connections and sensitive changes require platform approval.</div>

    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><p class="font-semibold">Please correct the following:</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-semibold">Configured connections</h2><p class="text-sm text-slate-500">One connection can be primary; others remain available for later routing.</p></div><a href="{{ route('parent-admin.provider-connections.index', ['create' => 1]) }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Add provider connection</a></div>
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse($connections as $connection)
                <a href="{{ route('parent-admin.provider-connections.index', ['edit' => $connection['id']]) }}" class="rounded-xl border border-slate-200 p-4 hover:border-blue-300">
                    <div class="flex items-center justify-between gap-2"><p class="font-semibold">{{ $connection['name'] }}</p><div class="flex gap-1">@if(data_get($connection, 'settings.is_primary'))<span class="rounded-full bg-blue-100 px-2 py-1 text-[10px] font-bold text-blue-700">PRIMARY</span>@endif<span class="rounded-full bg-amber-100 px-2 py-1 text-[10px] font-bold uppercase text-amber-700">{{ $connection['approval_status'] }}</span></div></div>
                    <p class="mt-1 text-xs text-slate-500">{{ data_get($connection, 'provider_connection.name') }}</p><p class="mt-3 truncate text-xs text-slate-400">{{ $connection['base_url'] ?: 'Service endpoints only' }}</p>
                    @if($connection['rejection_reason'])<p class="mt-3 text-xs font-medium text-red-600">{{ $connection['rejection_reason'] }}</p>@endif
                </a>
            @empty<p class="text-sm text-slate-500">No provider connection has been configured.</p>@endforelse
        </div>
    </section>

    @if($showForm)
    <form method="POST" action="{{ $editingConnection ? route('parent-admin.provider-connections.update', $editingConnection['id']) : route('parent-admin.provider-connections.store') }}" x-data="providerConnectionForm(@js($initialForm), @js($adapters), @js($products), @js($runtimeFields), @js($credentialFields))" x-init="initialize()" class="space-y-5">
        @csrf
        @if($editingConnection) @method('PUT') @endif
        <input type="hidden" name="is_primary" :value="form.is_primary ? 1 : 0">
        <input type="hidden" name="settings[request_parameters][0][key]" value="phone_number"><input type="hidden" name="settings[request_parameters][0][type]" value="runtime"><input type="hidden" name="settings[request_parameters][0][value]" value="phone_number">
        <input type="hidden" name="settings[success_conditions][0][key]" :value="config('data')?.success_conditions?.[0]?.key || 'status'"><input type="hidden" name="settings[success_conditions][0][value]" :value="config('data')?.success_conditions?.[0]?.value || 'success'">
        <input type="hidden" name="settings[success_message_path]" :value="config('data')?.success_message_path || 'data.message'"><input type="hidden" name="settings[failure_message_path]" :value="config('data')?.failure_message_path || 'error.message'"><input type="hidden" name="settings[expected_success_code]" :value="config('data')?.expected_success_code || 200">

        @if($editingConnection)<div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Changing endpoints, credentials, mappings, headers, or response rules returns this connection to pending platform approval.</div>@endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Provider information</h2><p class="mt-1 text-sm text-slate-500">Select a platform-approved adapter and identify this parent connection.</p>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <label class="text-sm">Approved adapter<select name="provider_connection_id" x-model="form.provider_connection_id" @change="adapterChanged()" required class="mt-1 w-full rounded-xl border-slate-200"><option value="">Select adapter</option><template x-for="adapter in adapters" :key="adapter.id"><option :value="adapter.id" x-text="`${adapter.name} · ${adapter.adapter}${adapter.status==='inactive'?' · inactive':''}`"></option></template></select></label>
                <label class="text-sm">Connection name<input name="name" x-model="form.name" required class="mt-1 w-full rounded-xl border-slate-200" placeholder="PaulTechs Primary"></label>
                <label class="text-sm">Base URL<input name="base_url" x-model="form.base_url" type="url" class="mt-1 w-full rounded-xl border-slate-200" placeholder="https://provider.example/api"></label>
                <label class="text-sm">Status<select name="status" x-model="form.status" class="mt-1 w-full rounded-xl border-slate-200"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
            </div><label class="mt-4 flex items-center gap-2 text-sm font-medium"><input x-model="form.is_primary" type="checkbox"> Make this the primary provider connection</label>
        </section>

        <section x-show="selectedAdapter" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Encrypted credentials</h2><p class="mt-1 text-sm text-slate-500">Leave a credential blank while editing to preserve its saved value.</p>
            <div class="mt-4 grid gap-3 md:grid-cols-3"><template x-for="field in allowedCredentialFields" :key="field"><label class="text-sm capitalize"><span x-text="field.replaceAll('_',' ')"></span><input :name="`credentials[${field}]`" x-model="form.credentials[field]" type="password" autocomplete="new-password" class="mt-1 w-full rounded-xl border-slate-200"></label></template></div>
        </section>

        <section x-show="selectedAdapter" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Service endpoints</h2><div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3"><template x-for="product in supportedProducts" :key="product.slug"><label class="text-sm"><span x-text="`${product.name} URL`"></span><input :name="`settings[endpoints][${product.slug}]`" x-model="form.settings.endpoints[product.slug]" type="url" class="mt-1 w-full rounded-xl border-slate-200"></label></template></div>
            <div class="mt-4 grid gap-3 md:grid-cols-2"><label class="text-sm">HTTP method<select name="settings[http_method]" x-model="form.settings.http_method" class="mt-1 w-full rounded-xl border-slate-200"><template x-for="method in allowedMethods" :key="method"><option :value="method" x-text="method"></option></template></select></label><label class="text-sm">Timeout in seconds<input name="settings[timeout_seconds]" x-model="form.settings.timeout_seconds" type="number" min="5" max="120" class="mt-1 w-full rounded-xl border-slate-200"></label></div>
        </section>

        <section x-show="selectedAdapter" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Product request configuration</h2><p class="mt-1 text-sm text-slate-500">Each product has its own payload, headers, network IDs and response rules.</p>
            <div class="mt-4 flex flex-wrap gap-2"><template x-for="product in supportedProducts" :key="product.slug"><button type="button" @click="activeProduct=product.slug" class="rounded-full border px-4 py-2 text-sm font-semibold" :class="activeProduct===product.slug?'border-blue-600 bg-blue-600 text-white':'border-slate-200 text-slate-600'" x-text="product.name"></button></template></div>

            <template x-for="product in supportedProducts" :key="product.slug"><div x-show="activeProduct===product.slug" class="mt-5 space-y-5 rounded-2xl bg-slate-50 p-4">
                <div><div class="flex items-center justify-between"><h3 class="font-semibold">Request mapping</h3><button type="button" @click="addMapping(product.slug)" class="rounded-lg border bg-white px-3 py-2 text-xs font-semibold">Add mapping</button></div><div class="mt-3 space-y-2"><template x-for="(row,index) in config(product.slug).request_parameters" :key="row.id"><div class="grid gap-2 md:grid-cols-[1fr_150px_1fr_auto]"><input :name="`settings[product_configs][${product.slug}][request_parameters][${index}][key]`" x-model="row.key" required placeholder="Provider key" class="rounded-lg border-slate-200"><select :name="`settings[product_configs][${product.slug}][request_parameters][${index}][type]`" x-model="row.type" class="rounded-lg border-slate-200"><option value="runtime">Runtime</option><option value="credential">Credential</option><option value="literal">Literal</option></select><select x-show="row.type==='runtime'" :name="row.type==='runtime'?`settings[product_configs][${product.slug}][request_parameters][${index}][value]`:null" x-model="row.value" class="rounded-lg border-slate-200"><template x-for="field in runtimeFields"><option :value="field" x-text="field"></option></template></select><select x-show="row.type==='credential'" :name="row.type==='credential'?`settings[product_configs][${product.slug}][request_parameters][${index}][value]`:null" x-model="row.value" class="rounded-lg border-slate-200"><template x-for="field in allowedCredentialFields"><option :value="field" x-text="field"></option></template></select><input x-show="row.type==='literal'" :name="row.type==='literal'?`settings[product_configs][${product.slug}][request_parameters][${index}][value]`:null" x-model="row.value" class="rounded-lg border-slate-200"><button type="button" @click="config(product.slug).request_parameters.splice(index,1)" class="text-sm font-semibold text-red-600">Remove</button></div></template></div></div>
                <div><div class="flex items-center justify-between"><div><h3 class="font-semibold">Request headers</h3><p class="text-xs text-slate-500">For Authorization, use Credential + api_public_key and put Bearer in the optional prefix.</p></div><button type="button" @click="addHeader(product.slug)" class="rounded-lg border bg-white px-3 py-2 text-xs font-semibold">Add header</button></div><div class="mt-3 space-y-3"><template x-for="(row,index) in config(product.slug).request_headers" :key="row.id"><div class="grid gap-2 rounded-xl border border-slate-200 bg-white p-3 md:grid-cols-2 xl:grid-cols-[1fr_140px_1fr_150px_150px_auto]"><label class="text-xs text-slate-500">Header name<input :name="`settings[product_configs][${product.slug}][request_headers][${index}][key]`" x-model="row.key" required placeholder="Authorization" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></label><label class="text-xs text-slate-500">Value source<select :name="`settings[product_configs][${product.slug}][request_headers][${index}][type]`" x-model="row.type" @change="headerTypeChanged(row)" class="mt-1 w-full rounded-lg border-slate-200 text-sm"><option value="credential">Credential</option><option value="runtime">Runtime</option><option value="literal">Literal</option></select></label><label class="text-xs text-slate-500">Value<select x-show="row.type==='credential'" :name="row.type==='credential'?`settings[product_configs][${product.slug}][request_headers][${index}][value]`:null" x-model="row.value" class="mt-1 w-full rounded-lg border-slate-200 text-sm"><template x-for="field in allowedCredentialFields"><option :value="field" x-text="field"></option></template></select><select x-show="row.type==='runtime'" :name="row.type==='runtime'?`settings[product_configs][${product.slug}][request_headers][${index}][value]`:null" x-model="row.value" class="mt-1 w-full rounded-lg border-slate-200 text-sm"><template x-for="field in runtimeFields"><option :value="field" x-text="field"></option></template></select><input x-show="row.type==='literal'" :name="row.type==='literal'?`settings[product_configs][${product.slug}][request_headers][${index}][value]`:null" x-model="row.value" placeholder="Static value" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></label><label class="text-xs text-slate-500">Prefix (optional)<input :name="`settings[product_configs][${product.slug}][request_headers][${index}][prefix]`" x-model="row.prefix" placeholder="Bearer " class="mt-1 w-full rounded-lg border-slate-200 text-sm"></label><label class="text-xs text-slate-500">Suffix (optional)<input :name="`settings[product_configs][${product.slug}][request_headers][${index}][suffix]`" x-model="row.suffix" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></label><button type="button" @click="config(product.slug).request_headers.splice(index,1)" class="self-end pb-2 text-sm font-semibold text-red-600">Remove</button></div></template></div></div>
                <div><h3 class="font-semibold">Network mapping</h3><div class="mt-3 grid gap-3 md:grid-cols-4"><template x-for="network in ['MTN','GLO','AIRTEL','9MOBILE']" :key="network"><label class="text-sm"><span x-text="network"></span><input :name="`settings[product_configs][${product.slug}][network_mapping][${network}]`" x-model="config(product.slug).network_mapping[network]" class="mt-1 w-full rounded-xl border-slate-200"></label></template></div></div>
                <div><div class="flex items-center justify-between"><h3 class="font-semibold">Success conditions</h3><button type="button" @click="addCondition(product.slug)" class="rounded-lg border bg-white px-3 py-2 text-xs font-semibold">Add condition</button></div><div class="mt-3 space-y-2"><template x-for="(row,index) in config(product.slug).success_conditions" :key="row.id"><div class="grid gap-2 md:grid-cols-[1fr_1fr_auto]"><input :name="`settings[product_configs][${product.slug}][success_conditions][${index}][key]`" x-model="row.key" required class="rounded-lg border-slate-200"><input :name="`settings[product_configs][${product.slug}][success_conditions][${index}][value]`" x-model="row.value" required class="rounded-lg border-slate-200"><button type="button" @click="config(product.slug).success_conditions.splice(index,1)" class="text-sm font-semibold text-red-600">Remove</button></div></template></div><div class="mt-3 grid gap-3 md:grid-cols-2"><label class="text-sm">Success message path<input :name="`settings[product_configs][${product.slug}][success_message_path]`" x-model="config(product.slug).success_message_path" required class="mt-1 w-full rounded-xl border-slate-200"></label><label class="text-sm">Failure message path<input :name="`settings[product_configs][${product.slug}][failure_message_path]`" x-model="config(product.slug).failure_message_path" required class="mt-1 w-full rounded-xl border-slate-200"></label></div></div>
            </div></template>
        </section>

        <div class="flex justify-end gap-3"><a href="{{ route('parent-admin.provider-connections.index') }}" class="rounded-xl border border-slate-200 px-5 py-3 font-semibold">Cancel</a><button class="rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white">{{ $editingConnection ? 'Update provider connection' : 'Submit for approval' }}</button></div>
    </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init',()=>Alpine.data('providerConnectionForm',(initial,adapters,products,runtimeFields,credentialFields)=>({
    form:initial,adapters,products,runtimeFields,credentialFields,activeProduct:'',
    get selectedAdapter(){return this.adapters.find(item=>String(item.id)===String(this.form.provider_connection_id))||null},
    get supportedProducts(){const services=this.selectedAdapter?.capabilities?.services;return this.products.filter(product=>!services||services.includes(product.slug))},
    get allowedMethods(){return this.selectedAdapter?.capabilities?.methods||['POST','GET']},
    get allowedCredentialFields(){return this.selectedAdapter?.capabilities?.credential_fields??this.credentialFields},
    blankConfig(){return {request_parameters:[{id:crypto.randomUUID(),key:'',type:'runtime',value:'phone_number'}],request_headers:[],network_mapping:{MTN:'',GLO:'',AIRTEL:'','9MOBILE':''},success_conditions:[{id:crypto.randomUUID(),key:'status',value:'success'}],success_message_path:'data.message',failure_message_path:'error.message',expected_success_code:200,expected_failure_code:null}},
    initialize(){this.form.credentials=this.form.credentials||{};this.form.settings=this.form.settings||{};this.form.settings.endpoints=this.form.settings.endpoints||{};this.form.settings.product_configs=this.form.settings.product_configs||{};this.ensureConfigs()},
    adapterChanged(){if(!this.allowedMethods.includes(this.form.settings.http_method))this.form.settings.http_method=this.allowedMethods[0]||'POST';this.ensureConfigs()},
    ensureConfigs(){this.supportedProducts.forEach(product=>{if(!this.form.settings.product_configs[product.slug])this.form.settings.product_configs[product.slug]=this.blankConfig();const config=this.form.settings.product_configs[product.slug];['request_parameters','request_headers','success_conditions'].forEach(key=>config[key]=(config[key]||[]).map(row=>({...row,id:row.id||crypto.randomUUID()})));config.network_mapping={MTN:'',GLO:'',AIRTEL:'','9MOBILE':'',...(config.network_mapping||{})};if(this.form.settings.endpoints[product.slug]===undefined)this.form.settings.endpoints[product.slug]=''});this.activeProduct=this.supportedProducts.some(product=>product.slug===this.activeProduct)?this.activeProduct:(this.supportedProducts[0]?.slug||'')},
    config(slug){return this.form.settings.product_configs[slug]},
    addMapping(slug){this.config(slug).request_parameters.push({id:crypto.randomUUID(),key:'',type:'runtime',value:'phone_number'})},
    addHeader(slug){this.config(slug).request_headers.push({id:crypto.randomUUID(),key:'',type:'credential',value:this.allowedCredentialFields[0]||'',prefix:'',suffix:''})},
    headerTypeChanged(row){row.value=row.type==='credential'?(this.allowedCredentialFields[0]||''):row.type==='runtime'?'phone_number':''},
    addCondition(slug){this.config(slug).success_conditions.push({id:crypto.randomUUID(),key:'',value:''})}
})))
</script>
@endpush
