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
                <p class="mt-3 text-sm leading-6 text-slate-300">Adapters define the complete reusable request, validation and response flow. Provider connections inherit it; parents only supply credentials.</p>
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
                            <template x-for="service in allowed.services" :key="service.slug"><label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 text-sm"><input type="checkbox" :value="service.slug" x-model="form.capabilities.services" @change="ensureSettings()" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"><span x-text="service.name"></span></label></template>
                        </div>
                        <span x-show="errors['capabilities.services']" class="mt-2 block text-xs text-rose-600" x-text="firstError('capabilities.services')"></span>
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm font-semibold text-slate-800">Allowed HTTP methods</legend>
                        <div class="mt-3 flex flex-wrap gap-3">
                            <template x-for="method in allowed.methods" :key="method"><label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm"><input type="checkbox" :value="method" x-model="form.capabilities.methods" @change="ensureSettings()" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"><span x-text="method"></span></label></template>
                        </div>
                    </fieldset>

                    @include('platform-admin.provider-adapters._configuration-builder')

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
    const blankSettings = () => ({ http_method: 'POST', timeout_seconds: 30, endpoints: {}, product_configs: {} });
    const emptyForm = () => ({ id: null, name: '', slug: '', adapter: '', status: 'active', settings: blankSettings(), expertJson: JSON.stringify(blankSettings(), null, 2), expertOpen: false, expertDirty: false, capabilities: { services: [], methods: ['POST'], credential_fields: [] } });
    return {
        adapters: [], allowed: { services: [], methods: [], credential_fields: [] }, loading: true, saving: false,
        modal: false, search: '', message: '', messageType: 'success', errors: {}, form: emptyForm(), activeService: '',
        runtimeFields: @json(\App\Http\Requests\ParentAdmin\SaveProviderConnectionRequest::RUNTIME_FIELDS),
        networkKeys: ['MTN','GLO','AIRTEL','9MOBILE','DSTV','GOTV','STARTIMES','PREPAID','POSTPAID'],
        get filteredAdapters() { const term = this.search.trim().toLowerCase(); return term ? this.adapters.filter(item => `${item.name} ${item.slug} ${item.adapter}`.toLowerCase().includes(term)) : this.adapters; },
        get selectedServices() { return this.allowed.services.filter(service => this.form.capabilities.services.includes(service.slug)); },
        async load() { this.loading = true; try { const response = await fetch('{{ route('platform-admin.provider-adapters.data') }}', { headers: { Accept: 'application/json' } }); const data = await response.json(); this.adapters = data.adapters; this.allowed = data.allowed; } finally { this.loading = false; } },
        openCreate() { this.form = emptyForm(); this.errors = {}; this.activeService = ''; this.modal = true; this.$nextTick(() => this.ensureSettings()); },
        openEdit(adapter) { const settings = JSON.parse(JSON.stringify(adapter.settings || blankSettings())); this.form = { id: adapter.id, name: adapter.name, slug: adapter.slug, adapter: adapter.adapter, status: adapter.status, settings, expertJson: JSON.stringify(this.cleanSettings(settings), null, 2), expertOpen: false, expertDirty: false, capabilities: { services: [...(adapter.capabilities?.services || [])], methods: [...(adapter.capabilities?.methods || [])], credential_fields: [...(adapter.capabilities?.credential_fields || [])] } }; this.errors = {}; this.modal = true; this.$nextTick(() => this.ensureSettings()); },
        credentialSummary(adapter) { const fields = adapter.capabilities?.credential_fields || []; return fields.length ? `Credentials: ${fields.join(', ')}` : 'No credentials required'; },
        firstError(key) { return this.errors[key]?.[0] || ''; },
        uid() { return globalThis.crypto?.randomUUID?.() || `${Date.now()}-${Math.random()}`; },
        row(row = {}) { return { _id: this.uid(), key: '', type: 'runtime', value: 'phone_number', prefix: '', suffix: '', ...row }; },
        blankConfig() { return { request_parameters: [this.row()], request_headers: [], network_mapping: {}, success_conditions: [{ _id: this.uid(), key: 'success', value: 'true' }], success_message_path: 'message', failure_message_path: 'message', actual_charge_path: '', expected_success_code: 200, expected_failure_code: null }; },
        ensureSettings() { this.form.settings = { ...blankSettings(), ...(this.form.settings || {}) }; this.form.settings.endpoints ||= {}; this.form.settings.product_configs ||= {}; this.selectedServices.forEach(service => { this.form.settings.endpoints[service.slug] ??= ''; this.form.settings.product_configs[service.slug] ||= this.blankConfig(); const config = this.form.settings.product_configs[service.slug]; config.request_parameters = (config.request_parameters || []).map(row => this.row(row)); config.request_headers = (config.request_headers || []).map(row => this.row(row)); config.success_conditions = (config.success_conditions || []).map(row => ({ _id: this.uid(), ...row })); config.network_mapping ||= {}; if (config.validation) this.normalizeValidation(config.validation); }); if (!this.form.capabilities.methods.includes(this.form.settings.http_method)) this.form.settings.http_method = this.form.capabilities.methods[0] || 'POST'; this.activeService = this.selectedServices.some(service => service.slug === this.activeService) ? this.activeService : (this.selectedServices[0]?.slug || ''); },
        config(slug) { this.form.settings.product_configs[slug] ||= this.blankConfig(); return this.form.settings.product_configs[slug]; },
        addMapping(slug) { this.config(slug).request_parameters.push(this.row()); },
        addHeader(slug) { this.config(slug).request_headers.push(this.row({ type: 'credential', value: this.form.capabilities.credential_fields[0] || '' })); },
        addCondition(slug) { this.config(slug).success_conditions.push({ _id: this.uid(), key: '', value: '' }); },
        mappingTypeChanged(row) { row.value = row.type === 'credential' ? (this.form.capabilities.credential_fields[0] || '') : row.type === 'runtime' ? 'phone_number' : ''; },
        isValidatable(slug) { return ['cable_subscription','utility_bills','cable','electricity'].includes(slug); },
        hasValidation(slug) { return !!this.config(slug).validation; },
        blankValidation(slug) { const field = ['utility_bills','electricity'].includes(slug) ? 'meter_number' : 'smartcard_number'; return { endpoint: '', http_method: 'POST', request_parameters: [this.row({ value: field })], request_headers: [], success_conditions: [{ _id: this.uid(), key: 'success', value: 'true' }], success_message_path: 'message', failure_message_path: 'message', customer_name_path: 'data.customer_name', customer_address_path: 'data.address', expected_success_code: 200 }; },
        normalizeValidation(validation) { validation.request_parameters = (validation.request_parameters || []).map(row => this.row(row)); validation.request_headers = (validation.request_headers || []).map(row => this.row(row)); validation.success_conditions = (validation.success_conditions || []).map(row => ({ _id: this.uid(), ...row })); },
        toggleValidation(slug, enabled) { if (enabled) this.config(slug).validation = this.blankValidation(slug); else delete this.config(slug).validation; },
        addValidationMapping(slug) { this.config(slug).validation.request_parameters.push(this.row()); },
        addValidationHeader(slug) { this.config(slug).validation.request_headers.push(this.row({ type: 'credential', value: this.form.capabilities.credential_fields[0] || '' })); },
        addValidationCondition(slug) { this.config(slug).validation.success_conditions.push({ _id: this.uid(), key: '', value: '' }); },
        toggleExpert(event) { this.form.expertOpen = event.target.open; if (event.target.open && !this.form.expertDirty) this.form.expertJson = JSON.stringify(this.cleanSettings(this.form.settings), null, 2); },
        applyExpertJson() { try { const settings = JSON.parse(this.form.expertJson || '{}'); if (!settings || Array.isArray(settings) || typeof settings !== 'object') throw new Error('invalid'); this.form.settings = settings; this.ensureSettings(); this.form.expertJson = JSON.stringify(this.cleanSettings(this.form.settings), null, 2); this.form.expertDirty = false; delete this.errors.settings; this.message = 'Advanced JSON applied to the guided form. Save the adapter to persist it.'; this.messageType = 'success'; } catch (error) { this.errors = { ...this.errors, settings: ['Adapter configuration must be a valid JSON object.'] }; } },
        cleanSettings(value) { if (Array.isArray(value)) return value.map(item => this.cleanSettings(item)); if (value && typeof value === 'object') return Object.fromEntries(Object.entries(value).filter(([key]) => key !== '_id').map(([key,item]) => [key,this.cleanSettings(item)])); return value; },
        async save() {
            this.saving = true; this.errors = {}; this.message = '';
            const url = this.form.id ? `{{ url('/admin/provider-adapters') }}/${this.form.id}` : '{{ route('platform-admin.provider-adapters.store') }}';
            try {
                this.ensureSettings();
                let settings = this.cleanSettings(this.form.settings); if (this.form.expertDirty) { try { settings = JSON.parse(this.form.expertJson || '{}'); } catch (error) { this.errors = { settings: ['Adapter configuration must be valid JSON.'] }; return; } }
                const payload = { ...this.form, settings }; delete payload.expertJson; delete payload.expertOpen;
                const response = await fetch(url, { method: this.form.id ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify(payload) });
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
