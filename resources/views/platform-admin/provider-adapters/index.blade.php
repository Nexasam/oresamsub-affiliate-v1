@extends('platform-admin.layouts.app')

@section('title', 'Provider adapters')
@section('eyebrow', 'Integrations')
@section('heading', 'Provider adapters')

@section('content')
<div x-data="providerAdapters()" x-init="load()" class="space-y-6">
    <section class="overflow-hidden rounded-3xl bg-slate-950 text-white shadow-xl">
        <div class="grid gap-8 p-6 lg:grid-cols-[1fr_auto] lg:items-end lg:p-9">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[.22em] text-emerald-400">Approved adapter catalogue</p>
                <h2 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Control which integration engines parents may configure.</h2>
                <p class="mt-3 text-sm leading-6 text-slate-300">Adapters define safe capabilities only. API URLs, credentials, request mappings and provider rules remain inside each parent business connection.</p>
            </div>
            <button @click="openCreate()" class="rounded-2xl bg-emerald-400 px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-emerald-300">Add provider adapter</button>
        </div>
    </section>

    <div x-show="message" x-cloak class="rounded-2xl border px-4 py-3 text-sm" :class="messageType === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'" x-text="message"></div>

    <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="font-semibold">Adapter definitions</h3>
                <p class="mt-1 text-sm text-slate-500">Deactivate an adapter to prevent new parent connections without disrupting saved configurations.</p>
            </div>
            <input x-model="search" type="search" placeholder="Search adapters" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 sm:w-64">
        </div>

        <div x-show="loading" class="p-10 text-center text-sm text-slate-500">Loading provider adapters…</div>
        <div x-show="!loading && filteredAdapters.length === 0" x-cloak class="p-10 text-center text-sm text-slate-500">No provider adapters match this view.</div>

        <div x-show="!loading && filteredAdapters.length" x-cloak class="grid gap-4 p-5 lg:grid-cols-2">
            <template x-for="adapter in filteredAdapters" :key="adapter.id">
                <article class="rounded-2xl border border-slate-200 p-5 transition hover:border-slate-300 hover:shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-semibold" x-text="adapter.name"></h4>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide" :class="adapter.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'" x-text="adapter.status"></span>
                            </div>
                            <p class="mt-1 font-mono text-xs text-slate-400"><span x-text="adapter.slug"></span> · <span x-text="adapter.adapter"></span></p>
                        </div>
                        <button @click="openEdit(adapter)" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</button>
                    </div>
                    <div class="mt-5 grid gap-4 sm:grid-cols-3">
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Services</p><p class="mt-1 text-sm text-slate-700" x-text="(adapter.capabilities?.services || []).join(', ') || 'None'"></p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Methods</p><p class="mt-1 text-sm text-slate-700" x-text="(adapter.capabilities?.methods || []).join(', ') || 'None'"></p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Parents</p><p class="mt-1 text-sm text-slate-700" x-text="adapter.parent_connections_count || 0"></p></div>
                    </div>
                    <p class="mt-4 text-xs text-slate-400" x-text="credentialSummary(adapter)"></p>
                </article>
            </template>
        </div>
    </section>

    <div x-show="modal" x-cloak class="fixed inset-0 z-[70] overflow-y-auto bg-slate-950/60 p-4 backdrop-blur-sm" @keydown.escape.window="modal = false">
        <div @click.outside="modal = false" class="mx-auto my-8 max-w-3xl rounded-3xl bg-white shadow-2xl">
            <form @submit.prevent="save()">
                <div class="flex items-start justify-between border-b border-slate-100 p-6">
                    <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-emerald-600">Platform catalogue</p><h3 class="mt-1 text-xl font-bold" x-text="form.id ? 'Edit provider adapter' : 'Add provider adapter'"></h3></div>
                    <button type="button" @click="modal = false" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700">✕</button>
                </div>

                <div class="space-y-7 p-6">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="text-sm font-medium text-slate-700">Display name
                            <input x-model="form.name" required class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Configurable HTTP">
                            <span x-show="errors.name" class="mt-1 block text-xs text-rose-600" x-text="firstError('name')"></span>
                        </label>
                        <label class="text-sm font-medium text-slate-700">Status
                            <select x-model="form.status" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                        </label>
                        <label class="text-sm font-medium text-slate-700">Slug
                            <input x-model="form.slug" required class="mt-2 w-full rounded-xl border-slate-200 font-mono text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="configurable-http">
                            <span x-show="errors.slug" class="mt-1 block text-xs text-rose-600" x-text="firstError('slug')"></span>
                        </label>
                        <label class="text-sm font-medium text-slate-700">Internal adapter key
                            <input x-model="form.adapter" required class="mt-2 w-full rounded-xl border-slate-200 font-mono text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="configurable_http">
                            <span x-show="errors.adapter" class="mt-1 block text-xs text-rose-600" x-text="firstError('adapter')"></span>
                        </label>
                    </div>

                    <fieldset>
                        <legend class="text-sm font-semibold text-slate-800">Supported services</legend>
                        <p class="mt-1 text-xs text-slate-500">Parents can configure endpoints only for selected services.</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <template x-for="service in allowed.services" :key="service"><label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 text-sm capitalize"><input type="checkbox" :value="service" x-model="form.capabilities.services" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"><span x-text="service"></span></label></template>
                        </div>
                        <span x-show="errors['capabilities.services']" class="mt-2 block text-xs text-rose-600" x-text="firstError('capabilities.services')"></span>
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm font-semibold text-slate-800">Allowed HTTP methods</legend>
                        <div class="mt-3 flex flex-wrap gap-3">
                            <template x-for="method in allowed.methods" :key="method"><label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm"><input type="checkbox" :value="method" x-model="form.capabilities.methods" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"><span x-text="method"></span></label></template>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm font-semibold text-slate-800">Credential fields</legend>
                        <p class="mt-1 text-xs text-slate-500">Select the secret inputs this adapter permits parent administrators to store.</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                            <template x-for="field in allowed.credential_fields" :key="field"><label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 text-sm"><input type="checkbox" :value="field" x-model="form.capabilities.credential_fields" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"><span class="font-mono text-xs" x-text="field"></span></label></template>
                        </div>
                    </fieldset>

                    <div x-show="Object.keys(errors).length" class="rounded-xl bg-rose-50 p-3 text-sm text-rose-700">Please correct the highlighted adapter details.</div>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-100 p-6">
                    <button type="button" @click="modal = false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
                    <button :disabled="saving" class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-50" x-text="saving ? 'Saving…' : 'Save adapter'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function providerAdapters() {
    const emptyForm = () => ({ id: null, name: '', slug: '', adapter: '', status: 'active', capabilities: { services: [], methods: ['POST'], credential_fields: [] } });
    return {
        adapters: [], allowed: { services: [], methods: [], credential_fields: [] }, loading: true, saving: false,
        modal: false, search: '', message: '', messageType: 'success', errors: {}, form: emptyForm(),
        get filteredAdapters() { const term = this.search.trim().toLowerCase(); return term ? this.adapters.filter(item => `${item.name} ${item.slug} ${item.adapter}`.toLowerCase().includes(term)) : this.adapters; },
        async load() { this.loading = true; try { const response = await fetch('{{ route('platform-admin.provider-adapters.data') }}', { headers: { Accept: 'application/json' } }); const data = await response.json(); this.adapters = data.adapters; this.allowed = data.allowed; } finally { this.loading = false; } },
        openCreate() { this.form = emptyForm(); this.errors = {}; this.modal = true; },
        openEdit(adapter) { this.form = { id: adapter.id, name: adapter.name, slug: adapter.slug, adapter: adapter.adapter, status: adapter.status, capabilities: { services: [...(adapter.capabilities?.services || [])], methods: [...(adapter.capabilities?.methods || [])], credential_fields: [...(adapter.capabilities?.credential_fields || [])] } }; this.errors = {}; this.modal = true; },
        credentialSummary(adapter) { const fields = adapter.capabilities?.credential_fields || []; return fields.length ? `Credentials: ${fields.join(', ')}` : 'No credentials required'; },
        firstError(key) { return this.errors[key]?.[0] || ''; },
        async save() {
            this.saving = true; this.errors = {}; this.message = '';
            const url = this.form.id ? `{{ url('/admin/provider-adapters') }}/${this.form.id}` : '{{ route('platform-admin.provider-adapters.store') }}';
            try {
                const response = await fetch(url, { method: this.form.id ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify(this.form) });
                const data = await response.json();
                if (!response.ok) { this.errors = data.errors || {}; this.message = data.message || 'Adapter could not be saved.'; this.messageType = 'error'; return; }
                this.modal = false; this.message = data.message; this.messageType = 'success'; await this.load();
            } catch (error) { this.message = 'Adapter could not be saved. Please try again.'; this.messageType = 'error'; }
            finally { this.saving = false; }
        },
    };
}
</script>
@endpush
