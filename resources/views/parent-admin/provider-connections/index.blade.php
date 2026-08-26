@extends('parent-admin.layouts.app')

@section('title', 'Provider connections')
@section('heading', 'Provider connections')

@php
    $saved = $editingConnection;
    $initial = old() ?: [
        'provider_adapter_id' => $saved['provider_adapter_id'] ?? '',
        'provider_connection_id' => $saved['provider_connection_id'] ?? '',
        'name' => $saved['name'] ?? '', 'status' => $saved['status'] ?? 'active',
        'is_primary' => (bool) data_get($saved, 'settings.is_primary', false),
        'unlisted' => ($saved['request_type'] ?? null) === 'discovery',
        'proposed_provider_name' => $saved['proposed_provider_name'] ?? '',
        'proposed_base_url' => $saved['proposed_base_url'] ?? '',
        'proposed_documentation_url' => $saved['proposed_documentation_url'] ?? '',
        'discovery_notes' => $saved['discovery_notes'] ?? '',
    ];
@endphp

@section('content')
<div class="space-y-5" x-data='simpleProviderConnection(@json($initial), @json($adapters), @json($providerOptions))'>
    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900"><strong>Simple provider setup:</strong> choose the technology adapter and provider website, then enter only your credentials. The platform manages endpoints, mappings and response rules.</div>
    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><p class="font-semibold">Please correct the following:</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="workspace-panel">
        <div class="workspace-panel-header"><div><h2 class="font-semibold">Configured connections</h2><p class="mt-1 text-sm text-slate-500">Credentials are encrypted. New connections and credential changes require platform approval.</p></div><a href="{{ route('parent-admin.provider-connections.index', ['create' => 1]) }}" class="workspace-btn-primary">Add connection</a></div>
        <div class="grid gap-3 p-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse($connections as $connection)
            <a href="{{ route('parent-admin.provider-connections.index', ['edit' => $connection['id']]) }}" class="rounded-xl border border-slate-200 p-4 hover:border-blue-300 dark:border-slate-700">
                <div class="flex items-center justify-between gap-2"><p class="font-semibold">{{ $connection['name'] }}</p><span class="rounded-full bg-amber-100 px-2 py-1 text-[10px] font-bold uppercase text-amber-700">{{ $connection['approval_status'] }}</span></div>
                <p class="mt-1 text-xs text-slate-500">{{ data_get($connection, 'provider_connection.name') ?: ($connection['proposed_provider_name'] ?: 'New provider request') }} · {{ data_get($connection, 'provider_adapter.name') }}</p>
                <p class="mt-3 text-xs text-slate-400">{{ collect($connection['credential_status'])->filter()->count() }} credentials supplied @if(data_get($connection, 'settings.is_primary')) · Primary @endif</p>
            </a>
        @empty<div class="col-span-full p-6 text-center text-sm text-slate-500">No provider connection has been configured.</div>@endforelse
        </div>
    </section>

    @if($showForm)
    <form method="POST" action="{{ $editingConnection ? route('parent-admin.provider-connections.update', $editingConnection['id']) : route('parent-admin.provider-connections.store') }}" class="workspace-panel overflow-visible">
        @csrf @if($editingConnection) @method('PUT') @endif
        <div class="workspace-panel-header"><div><h2 class="font-semibold">{{ $editingConnection ? 'Update connection' : 'Connect a provider' }}</h2><p class="mt-1 text-sm text-slate-500">No technical API mapping is required from you.</p></div></div>
        <div class="grid gap-5 p-5 md:grid-cols-2">
            <label class="grid gap-1 text-sm font-semibold">Technology adapter<select name="provider_adapter_id" x-model="form.provider_adapter_id" @change="adapterChanged" required class="rounded-xl border-slate-200"><option value="">Choose adapter</option><template x-for="adapter in adapters" :key="adapter.id"><option :value="adapter.id" x-text="adapter.name"></option></template></select><span class="text-xs font-normal text-slate-500">Example: MSORG.</span></label>
            <label class="grid gap-1 text-sm font-semibold">Provider website<select name="provider_connection_id" x-model="form.provider_connection_id" @change="form.unlisted = form.provider_connection_id === ''" :disabled="!form.provider_adapter_id || form.unlisted" class="rounded-xl border-slate-200"><option value="">My provider is not listed</option><template x-for="provider in matchingProviders" :key="provider.id"><option :value="provider.id" x-text="provider.name"></option></template></select><span class="text-xs font-normal text-slate-500">Only providers using the selected adapter are shown.</span></label>
            <label class="flex items-center gap-2 md:col-span-2"><input type="checkbox" x-model="form.unlisted" @change="if(form.unlisted) form.provider_connection_id=''" class="rounded border-slate-300"> <span class="text-sm font-semibold">My provider website is not listed</span></label>

            <template x-if="form.unlisted"><div class="contents">
                <label class="grid gap-1 text-sm font-semibold">Provider name<input name="proposed_provider_name" x-model="form.proposed_provider_name" :required="form.unlisted" class="rounded-xl border-slate-200" placeholder="PaulTechs"></label>
                <label class="grid gap-1 text-sm font-semibold">Provider website/API URL<input name="proposed_base_url" x-model="form.proposed_base_url" type="url" :required="form.unlisted" class="rounded-xl border-slate-200" placeholder="https://paultechs.com"></label>
                <label class="grid gap-1 text-sm font-semibold">Documentation URL<input name="proposed_documentation_url" x-model="form.proposed_documentation_url" type="url" class="rounded-xl border-slate-200"></label>
                <label class="grid gap-1 text-sm font-semibold">Notes<textarea name="discovery_notes" x-model="form.discovery_notes" rows="3" class="rounded-xl border-slate-200" placeholder="This website uses the selected adapter."></textarea></label>
                <div class="md:col-span-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">The platform team will verify this website and create a reusable provider connection before approving your request.</div>
            </div></template>

            <label class="grid gap-1 text-sm font-semibold">Connection name<input name="name" x-model="form.name" required class="rounded-xl border-slate-200" placeholder="Primary provider"></label>
            <label class="grid gap-1 text-sm font-semibold">Status<select name="status" x-model="form.status" class="rounded-xl border-slate-200"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>

            <div class="md:col-span-2"><h3 class="font-semibold">Provider credentials</h3><p class="mt-1 text-xs text-slate-500">Values are encrypted and cannot be viewed after saving. Leave an existing value blank to keep it.</p><div class="mt-3 grid gap-4 md:grid-cols-2"><template x-for="field in credentialFields" :key="field"><label class="grid gap-1 text-sm font-semibold"><span x-text="headline(field)"></span><input type="password" :name="`credentials[${field}]`" autocomplete="new-password" class="rounded-xl border-slate-200" :placeholder="credentialSupplied(field) ? '•••••••• (leave blank to keep)' : 'Required credential'"></label></template><p x-show="credentialFields.length===0" class="text-sm text-slate-500">This adapter requires no credentials.</p></div></div>
            <label class="flex items-center gap-2 md:col-span-2"><input type="hidden" name="is_primary" value="0"><input type="checkbox" name="is_primary" value="1" x-model="form.is_primary" class="rounded border-slate-300"> <span class="text-sm font-semibold">Use as primary provider route</span></label>
        </div>
        <div class="flex justify-end gap-3 border-t border-slate-100 p-5"><a href="{{ route('parent-admin.provider-connections.index') }}" class="workspace-btn-secondary">Cancel</a><button class="workspace-btn-primary">Submit for approval</button></div>
    </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
function simpleProviderConnection(initial, adapters, providers){return {form:initial,adapters,providers,get selectedAdapter(){return this.adapters.find(a=>String(a.id)===String(this.form.provider_adapter_id))},get selectedProvider(){return this.providers.find(p=>String(p.id)===String(this.form.provider_connection_id))},get matchingProviders(){return this.providers.filter(p=>String(p.provider_adapter_id)===String(this.form.provider_adapter_id))},get credentialFields(){return this.selectedProvider?.capabilities?.credential_fields||this.selectedAdapter?.capabilities?.credential_fields||[]},adapterChanged(){if(!this.matchingProviders.some(p=>String(p.id)===String(this.form.provider_connection_id)))this.form.provider_connection_id='';this.form.unlisted=this.matchingProviders.length===0},headline(value){return value.replaceAll('_',' ').replace(/\b\w/g,c=>c.toUpperCase())},credentialSupplied(field){return @json($editingConnection['credential_status'] ?? [])[field]===true}}}
</script>
@endpush
