@extends('platform-admin.layouts.app')
@section('title', 'Provider connection catalogue')
@section('heading', 'Provider connection catalogue')
@section('content')
@php
    $catalogueConnections = $connections->map(fn ($connection) => [
        'id' => $connection->id,
        'provider_adapter_id' => $connection->provider_adapter_id,
        'name' => $connection->name,
        'slug' => $connection->slug,
        'website_url' => $connection->website_url,
        'base_url' => $connection->base_url,
        'documentation_url' => $connection->documentation_url,
        'status' => $connection->status,
        'settings' => $connection->settings ?? [],
    ])->values();
@endphp
<div class="space-y-6" x-data="providerCatalogue()">
    <section x-ref="formSection" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold" x-text="editingId ? 'Edit provider connection' : 'Add provider connection'"></h2>
                <p class="mt-1 text-sm text-slate-500" x-text="editingId ? 'Update the reusable provider definition without replacing existing parent links.' : 'Choose an adapter, then override only values unique to this provider.'"></p>
            </div>
            <button x-show="editingId" type="button" @click="cancelEdit" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Cancel editing
            </button>
        </div>

        <form class="mt-5 grid gap-4 md:grid-cols-2" @submit.prevent="save">
            <label class="grid gap-1 text-sm font-semibold">
                Adapter
                <select x-model="form.provider_adapter_id" @change="selectAdapter" required class="rounded-xl border-slate-200">
                    <option value="">Choose adapter</option>
                    @foreach($adapters as $adapter)
                        <option value="{{ $adapter->id }}">{{ $adapter->name }} · v{{ $adapter->version }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1 text-sm font-semibold">Provider name<input x-model="form.name" required class="rounded-xl border-slate-200" placeholder="PaulTechs"></label>
            <label class="grid gap-1 text-sm font-semibold">Slug<input x-model="form.slug" required class="rounded-xl border-slate-200" placeholder="paultechs"></label>
            <label class="grid gap-1 text-sm font-semibold">Status
                <select x-model="form.status" required class="rounded-xl border-slate-200">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>
            <label class="grid gap-1 text-sm font-semibold">Website URL<input x-model="form.website_url" type="url" class="rounded-xl border-slate-200" placeholder="https://paultechs.com"></label>
            <label class="grid gap-1 text-sm font-semibold">Base/API URL<input x-model="form.base_url" type="url" class="rounded-xl border-slate-200" placeholder="https://paultechs.com/api"></label>
            <label class="grid gap-1 text-sm font-semibold md:col-span-2">Documentation URL<input x-model="form.documentation_url" type="url" class="rounded-xl border-slate-200"></label>
            <label class="grid gap-1 text-sm font-semibold md:col-span-2">
                Complete effective provider configuration
                <textarea x-model="overridesJson" rows="16" class="rounded-xl border-slate-200 font-mono text-xs" placeholder='{"endpoints":{"data":"https://provider.com/api/data"}}'></textarea>
                <span class="font-normal text-slate-500">Editing shows the complete saved settings. Selecting another adapter replaces this editor with that adapter's current defaults.</span>
            </label>
            <div class="flex flex-wrap items-center gap-3 md:col-span-2">
                <p x-show="error" x-text="error" class="w-full text-sm text-rose-600"></p>
                <button class="rounded-xl bg-emerald-600 px-5 py-3 font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60" :disabled="saving" x-text="saving ? 'Saving…' : (editingId ? 'Save changes' : 'Create connection')"></button>
                <button x-show="editingId" type="button" @click="cancelEdit" class="rounded-xl border border-slate-200 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b px-6 py-4"><h2 class="font-bold">Approved provider catalogue</h2></div>
        <div class="divide-y">
            @forelse($connections as $connection)
                <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
                    <div class="min-w-0">
                        <div class="font-semibold">{{ $connection->name }}</div>
                        <div class="mt-1 break-all text-xs text-slate-500">{{ $connection->providerAdapter?->name ?? 'Legacy adapter' }} · {{ $connection->website_url ?: $connection->base_url ?: 'No website recorded' }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $connection->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($connection->status) }}</span>
                        <button type="button" @click="editConnection({{ $connection->id }})" aria-label="Edit {{ $connection->name }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">
                            Edit
                        </button>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-500">No provider connections yet.</div>
            @endforelse
        </div>
    </section>
</div>

<script>
function providerCatalogue() {
    const blankForm = () => ({
        provider_adapter_id: '',
        name: '',
        slug: '',
        website_url: '',
        base_url: '',
        documentation_url: '',
        status: 'active',
    });

    return {
        saving: false,
        error: '',
        editingId: null,
        overridesJson: '{}',
        adapters: @json($adapters),
        connections: @json($catalogueConnections),
        form: blankForm(),

        selectAdapter() {
            const adapter = this.adapters.find(item => String(item.id) === String(this.form.provider_adapter_id));
            this.overridesJson = JSON.stringify(adapter?.settings || {}, null, 2);
        },

        editConnection(id) {
            const connection = this.connections.find(item => Number(item.id) === Number(id));
            if (! connection) return;

            this.editingId = connection.id;
            this.error = '';
            this.form = {
                provider_adapter_id: String(connection.provider_adapter_id || ''),
                name: connection.name || '',
                slug: connection.slug || '',
                website_url: connection.website_url || '',
                base_url: connection.base_url || '',
                documentation_url: connection.documentation_url || '',
                status: connection.status || 'active',
            };
            this.overridesJson = JSON.stringify(connection.settings || {}, null, 2);
            this.$nextTick(() => this.$refs.formSection.scrollIntoView({behavior: 'smooth', block: 'start'}));
        },

        cancelEdit() {
            this.editingId = null;
            this.error = '';
            this.form = blankForm();
            this.overridesJson = '{}';
        },

        async save() {
            this.error = '';
            let settings_overrides;
            try {
                settings_overrides = JSON.parse(this.overridesJson || '{}');
            } catch (error) {
                this.error = 'Provider configuration must be valid JSON.';
                return;
            }

            this.saving = true;
            const endpoint = this.editingId
                ? `{{ url('/admin/provider-connections/catalogue') }}/${this.editingId}`
                : `{{ route('platform-admin.provider-connections.catalogue.store') }}`;

            try {
                const response = await fetch(endpoint, {
                    method: this.editingId ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({...this.form, settings_overrides}),
                });
                const data = await response.json();
                if (! response.ok) {
                    this.error = Object.values(data.errors || {}).flat()[0] || data.message || 'Unable to save connection.';
                    return;
                }
                window.location.reload();
            } catch (error) {
                this.error = 'Unable to reach the server. Please try again.';
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endsection
