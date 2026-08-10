@extends('platform-admin.layouts.app')

@section('title', 'Provider connection reviews')
@section('eyebrow', 'Integrations')
@section('heading', 'Provider connection reviews')

@section('content')
<div x-data="connectionReviews()" x-init="load()" class="space-y-6">
    <section class="rounded-3xl bg-slate-950 p-7 text-white shadow-xl">
        <p class="text-xs font-semibold uppercase tracking-[.22em] text-emerald-400">Pending approval</p>
        <h2 class="mt-3 text-2xl font-bold">Review parent API configurations without exposing their secrets.</h2>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">Check provider identity, endpoints, mappings, headers and success rules. Credential values remain encrypted and hidden.</p>
        <div class="mt-6 flex flex-wrap gap-3"><template x-for="status in ['pending','approved','rejected']"><button @click="filter=status" class="rounded-full px-4 py-2 text-xs font-bold capitalize" :class="filter===status?'bg-emerald-400 text-slate-950':'bg-white/10 text-white'"><span x-text="status"></span> <span x-text="counts[status]||0"></span></button></template></div>
    </section>

    <div x-show="notice" x-cloak class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700" x-text="notice"></div>
    <div x-show="error" x-cloak class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="error"></div>

    <section class="grid gap-4 xl:grid-cols-2">
        <template x-for="connection in filtered" :key="connection.id">
            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400" x-text="connection.parent_business.name"></p><h3 class="mt-1 text-lg font-bold" x-text="connection.name"></h3><p class="mt-1 text-xs text-slate-500" x-text="`${connection.provider_connection.name} · ${connection.provider_connection.adapter}`"></p></div><span class="rounded-full px-3 py-1 text-xs font-bold capitalize" :class="badge(connection.approval_status)" x-text="connection.approval_status"></span></div>
                <div class="mt-5 grid gap-3 text-sm sm:grid-cols-2"><div><p class="text-xs font-semibold text-slate-400">Base URL</p><p class="mt-1 break-all" x-text="connection.base_url||'Endpoint-specific configuration'"></p></div><div><p class="text-xs font-semibold text-slate-400">Method / timeout</p><p class="mt-1" x-text="`${connection.settings.http_method||'—'} · ${connection.settings.timeout_seconds||'—'} seconds`"></p></div></div>
                <details class="mt-5 rounded-2xl bg-slate-50 p-4"><summary class="cursor-pointer text-sm font-semibold">Inspect configuration</summary><div class="mt-4 space-y-4 text-xs"><div><p class="font-semibold text-slate-500">Service endpoints</p><template x-for="(url,service) in connection.settings.endpoints||{}"><p class="mt-1 break-all"><span class="font-medium" x-text="service"></span>: <span x-text="url||'Not configured'"></span></p></template></div><div><p class="font-semibold text-slate-500">Credential presence</p><p class="mt-1" x-text="Object.entries(connection.credential_status).filter(([,set])=>set).map(([key])=>key).join(', ')||'No saved credentials'"></p></div><div><p class="font-semibold text-slate-500">Request mappings</p><pre class="mt-1 overflow-x-auto whitespace-pre-wrap" x-text="JSON.stringify(connection.settings.request_parameters||[],null,2)"></pre></div><div><p class="font-semibold text-slate-500">Headers (redacted placeholders)</p><pre class="mt-1 overflow-x-auto whitespace-pre-wrap" x-text="JSON.stringify(connection.settings.request_headers||[],null,2)"></pre></div><div><p class="font-semibold text-slate-500">Success rules</p><pre class="mt-1 overflow-x-auto whitespace-pre-wrap" x-text="JSON.stringify(connection.settings.success_conditions||[],null,2)"></pre></div></div></details>
                <p x-show="connection.rejection_reason" class="mt-4 rounded-xl bg-rose-50 p-3 text-sm text-rose-700" x-text="connection.rejection_reason"></p>
                <div x-show="connection.approval_status==='pending'" class="mt-5 flex flex-col gap-3 sm:flex-row"><button @click="review(connection,'approve')" :disabled="saving" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-50">Approve connection</button><button @click="openReject(connection)" class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-bold text-rose-700">Reject with reason</button></div>
            </article>
        </template>
        <p x-show="!loading && filtered.length===0" class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-sm text-slate-500">No connections in this review state.</p>
    </section>

    <div x-show="rejecting" x-cloak class="fixed inset-0 z-[70] bg-slate-950/60 p-4"><form @submit.prevent="submitRejection()" @click.outside="rejecting=null" class="mx-auto mt-24 max-w-lg rounded-3xl bg-white p-6 shadow-2xl"><h3 class="text-lg font-bold">Reject provider connection</h3><p class="mt-1 text-sm text-slate-500">Give the parent a clear correction they can act on.</p><textarea x-model="reason" required minlength="10" rows="5" class="mt-4 w-full rounded-xl border-slate-200" placeholder="Explain what must be corrected"></textarea><div class="mt-4 flex justify-end gap-3"><button type="button" @click="rejecting=null" class="rounded-xl border px-4 py-2 text-sm font-semibold">Cancel</button><button class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-bold text-white">Reject connection</button></div></form></div>
</div>
@endsection

@push('scripts')
<script>
function connectionReviews(){return{connections:[],counts:{},filter:'pending',loading:true,saving:false,notice:'',error:'',rejecting:null,reason:'',get filtered(){return this.connections.filter(item=>item.approval_status===this.filter)},badge(status){return status==='approved'?'bg-emerald-50 text-emerald-700':status==='rejected'?'bg-rose-50 text-rose-700':'bg-amber-50 text-amber-700'},async load(){this.loading=true;try{const response=await fetch('{{ route('platform-admin.provider-connections.data') }}',{headers:{Accept:'application/json'}});const data=await response.json();this.connections=data.connections;this.counts=data.counts}catch(e){this.error='Unable to load provider connections.'}finally{this.loading=false}},openReject(connection){this.rejecting=connection;this.reason=''},async submitRejection(){await this.review(this.rejecting,'reject',this.reason);this.rejecting=null},async review(connection,action,reason=null){this.saving=true;this.error='';try{const response=await fetch(`{{ url('/admin/provider-connections') }}/${connection.id}/review`,{method:'PATCH',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({action,reason})});const data=await response.json();if(!response.ok){this.error=Object.values(data.errors||{}).flat()[0]||data.message;return}this.notice=data.message;await this.load()}catch(e){this.error='Unable to review this connection.'}finally{this.saving=false}}}}
</script>
@endpush
