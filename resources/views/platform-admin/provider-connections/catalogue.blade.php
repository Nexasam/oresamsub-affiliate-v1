@extends('platform-admin.layouts.app')
@section('title', 'Provider connection catalogue')
@section('heading', 'Provider connection catalogue')
@section('content')
<div class="space-y-6" x-data="providerCatalogue()">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-bold">Add provider connection</h2><p class="mt-1 text-sm text-slate-500">Choose an adapter, then override only values unique to this provider.</p></div></div>
        <form class="mt-5 grid gap-4 md:grid-cols-2" @submit.prevent="save">
            <label class="grid gap-1 text-sm font-semibold">Adapter<select x-model="form.provider_adapter_id" @change="selectAdapter" required class="rounded-xl border-slate-200"><option value="">Choose adapter</option>@foreach($adapters as $adapter)<option value="{{ $adapter->id }}">{{ $adapter->name }} · v{{ $adapter->version }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-sm font-semibold">Provider name<input x-model="form.name" required class="rounded-xl border-slate-200" placeholder="PaulTechs"></label>
            <label class="grid gap-1 text-sm font-semibold">Slug<input x-model="form.slug" required class="rounded-xl border-slate-200" placeholder="paultechs"></label>
            <label class="grid gap-1 text-sm font-semibold">Website URL<input x-model="form.website_url" type="url" class="rounded-xl border-slate-200" placeholder="https://paultechs.com"></label>
            <label class="grid gap-1 text-sm font-semibold">Base/API URL<input x-model="form.base_url" type="url" class="rounded-xl border-slate-200" placeholder="https://paultechs.com/api"></label>
            <label class="grid gap-1 text-sm font-semibold">Documentation URL<input x-model="form.documentation_url" type="url" class="rounded-xl border-slate-200"></label>
            <label class="grid gap-1 text-sm font-semibold md:col-span-2">Provider configuration (prefilled from adapter)<textarea x-model="overridesJson" rows="12" class="rounded-xl border-slate-200 font-mono text-xs" placeholder='{"endpoints":{"data":"https://provider.com/api/data"}}'></textarea><span class="font-normal text-slate-500">All adapter settings are copied here. Change only the provider-specific URLs or rules that differ.</span></label>
            <div class="md:col-span-2"><p x-show="error" x-text="error" class="mb-3 text-sm text-rose-600"></p><button class="rounded-xl bg-emerald-600 px-5 py-3 font-semibold text-white" :disabled="saving" x-text="saving ? 'Saving…' : 'Create connection'"></button></div>
        </form>
    </section>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b px-6 py-4"><h2 class="font-bold">Approved provider catalogue</h2></div>
        <div class="divide-y">@forelse($connections as $connection)<div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4"><div><div class="font-semibold">{{ $connection->name }}</div><div class="text-xs text-slate-500">{{ $connection->providerAdapter?->name ?? 'Legacy adapter' }} · {{ $connection->website_url ?: $connection->base_url ?: 'No website recorded' }}</div></div><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $connection->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($connection->status) }}</span></div>@empty<div class="p-8 text-center text-sm text-slate-500">No provider connections yet.</div>@endforelse</div>
    </section>
</div>
<script>
function providerCatalogue(){return {saving:false,error:'',overridesJson:'{}',adapters:@json($adapters),form:{provider_adapter_id:'',name:'',slug:'',website_url:'',base_url:'',documentation_url:'',status:'active'},selectAdapter(){const adapter=this.adapters.find(item=>String(item.id)===String(this.form.provider_adapter_id));this.overridesJson=JSON.stringify(adapter?.settings||{},null,2)},async save(){this.error='';let settings_overrides;try{settings_overrides=JSON.parse(this.overridesJson||'{}')}catch(e){this.error='Provider configuration must be valid JSON.';return}this.saving=true;try{const response=await fetch('{{ route('platform-admin.provider-connections.catalogue.store') }}',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({...this.form,settings_overrides})});const data=await response.json();if(!response.ok){this.error=Object.values(data.errors||{}).flat()[0]||data.message||'Unable to save connection.';return}window.location.reload()}finally{this.saving=false}}}}
</script>
@endsection
