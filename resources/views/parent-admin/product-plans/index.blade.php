@extends('parent-admin.layouts.app')

@section('title', 'Product plans')
@section('heading', 'Manage product plans')

@section('content')
<div class="space-y-5" x-data="{ mode: 'single', drawerOpen: false, selectedPlan: null, selectedIds: [], selectionScope: 'selected', openDrawer(plan) { this.selectedPlan = JSON.parse(JSON.stringify(plan)); this.drawerOpen = true }, closeDrawer() { this.drawerOpen = false; this.selectedPlan = null } }" @keydown.escape.window="closeDrawer()">
    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">Only plans owned by <strong>{{ auth('parent_admin')->user()->parentBusiness->name }}</strong> appear here. Global categories are shared; provider routing and reseller prices belong to this parent.</div>
    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><p class="font-semibold">Please correct the following:</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="rounded-2xl border-2 border-blue-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div><p class="text-xs font-bold uppercase tracking-wider text-blue-600">Bulk migration</p><h2 class="mt-1 text-lg font-semibold">Import product plans from Excel</h2><p class="mt-1 max-w-2xl text-sm text-slate-500">Download your parent-specific workbook. It contains dropdowns for your approved connections, global categories and visibility settings. Existing provider-plan matches are safely shown as updates.</p></div>
            <a href="{{ route('parent-admin.product-plans.import.template') }}" class="rounded-xl border border-blue-300 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-800">1. Download Excel template</a>
        </div>
        <form method="POST" enctype="multipart/form-data" action="{{ route('parent-admin.product-plans.import.preview') }}" class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5">@csrf
            <label for="plans_file" class="block text-sm font-bold text-slate-800">2. Upload completed workbook</label>
            <p class="mt-1 text-xs text-slate-500">Accepted: .xlsx (recommended) or compatible .csv · Maximum size: 10 MB · Nothing is saved until you confirm the preview.</p>
            <div class="mt-4 flex flex-wrap items-center gap-3"><input id="plans_file" type="file" name="plans_file" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required class="block min-w-0 flex-1 rounded-xl border border-slate-300 bg-white p-3 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:font-semibold file:text-white"><button class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white">3. Upload and preview</button></div>
        </form>
        @if(session('import_errors'))<div class="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-700">@foreach(session('import_errors') as $line=>$error)<p>Row {{ $line }}: {{ $error }}</p>@endforeach</div>@endif
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4"><div><h2 class="text-lg font-semibold">Add product plan</h2><p class="text-sm text-slate-500">Create one complete plan or submit several plans atomically.</p></div><div class="flex rounded-xl bg-slate-100 p-1"><button type="button" @click="mode='single'" class="rounded-lg px-4 py-2 text-sm font-semibold" :class="mode==='single'?'bg-white shadow-sm':'text-slate-500'">Single plan</button><button type="button" @click="mode='bulk'" class="rounded-lg px-4 py-2 text-sm font-semibold" :class="mode==='bulk'?'bg-white shadow-sm':'text-slate-500'">Bulk addition</button></div></div>
        @if($connections->isEmpty())<div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">No approved provider connection is available. You can save hidden drafts, but an external parent cannot activate a plan until a connection is approved.</div>@endif

        <form method="POST" action="{{ route('parent-admin.product-plans.store') }}" x-show="mode==='single'" class="mt-6 space-y-6">
            @csrf
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="text-sm font-medium">Plan name<input name="product_plan_name" value="{{ old('product_plan_name') }}" required class="mt-1 w-full rounded-xl border-slate-200" placeholder="MTN SME 1GB"></label>
                <label class="text-sm font-medium">Global category<select name="product_plan_category_id" required class="mt-1 w-full rounded-xl border-slate-200"><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('product_plan_category_id') === (string) $category->id)>{{ $category->product_plan_category_name }} · {{ $category->network?->network_name ?: ($category->product?->product_name ?: 'General') }}</option>@endforeach</select></label>
                <label class="text-sm font-medium">Internal reference (optional)<input name="api_id" value="{{ old('api_id') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="PAUL-MTN-1GB"></label>
                <label class="text-sm font-medium">Provider cost<input name="cost_price" value="{{ old('cost_price') }}" required type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-sm font-medium">Admin/reference cost<input name="admin_cost_price" value="{{ old('admin_cost_price') }}" type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-sm font-medium">Data size in MB<input name="data_size_in_mb" value="{{ old('data_size_in_mb') }}" type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-sm font-medium">Validity in days<input name="validity_in_days" value="{{ old('validity_in_days') }}" type="number" min="0" class="mt-1 w-full rounded-xl border-slate-200"></label>
                <label class="text-sm font-medium">Profit mode<select name="profit_category" class="mt-1 w-full rounded-xl border-slate-200"><option value="flat" @selected(old('profit_category', 'flat') === 'flat')>Flat profit</option><option value="percent" @selected(old('profit_category') === 'percent')>Percentage profit</option></select></label>
            </div>

            <div class="rounded-2xl border border-slate-200 p-5"><h3 class="font-semibold">Primary provider mapping</h3><p class="mt-1 text-sm text-slate-500">The external plan ID is the value the selected provider expects during processing.</p><div class="mt-4 grid gap-4 md:grid-cols-2"><label class="text-sm font-medium">Approved provider connection<select name="route[parent_provider_connection_id]" class="mt-1 w-full rounded-xl border-slate-200"><option value="">No route — save as draft</option>@foreach($connections as $connection)<option value="{{ $connection->id }}" @selected((string) old('route.parent_provider_connection_id') === (string) $connection->id)>{{ $connection->name }} · {{ $connection->providerConnection?->name }}</option>@endforeach</select></label><label class="text-sm font-medium">Provider external plan ID<input name="route[provider_plan_id]" value="{{ old('route.provider_plan_id') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="111"></label></div></div>

            <div class="rounded-2xl border border-slate-200 p-5"><h3 class="font-semibold">Reseller acquisition prices</h3><div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">@forelse($levels as $index => $level)<div class="rounded-xl bg-slate-50 p-4"><p class="text-sm font-semibold">{{ $level->name }}</p><input type="hidden" name="prices[{{ $index }}][parent_reseller_level_id]" value="{{ $level->id }}"><div class="mt-3 grid grid-cols-2 gap-2"><label class="text-xs text-slate-500">Selling price<input name="prices[{{ $index }}][selling_price]" value="{{ old("prices.{$index}.selling_price") }}" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-200"></label><label class="text-xs text-slate-500">Maximum profit<input name="prices[{{ $index }}][max_profit]" value="{{ old("prices.{$index}.max_profit") }}" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-200"></label></div></div>@empty<p class="text-sm text-slate-500">Create reseller levels from the pricing workspace.</p>@endforelse</div></div>

            <details class="rounded-2xl border border-slate-200 p-5"><summary class="cursor-pointer font-semibold">Commission settings</summary><input type="hidden" name="commission_feature" value="0"><div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4"><label class="text-sm">Mode<select name="upline_commission_option" class="mt-1 w-full rounded-xl border-slate-200"><option value="flat">Flat</option><option value="percent">Percentage</option></select></label><label class="text-sm">Flat commission<input name="upline_flat_commission" value="{{ old('upline_flat_commission', 0) }}" type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-200"></label><label class="text-sm">Percentage<input name="upline_percentage_commission" value="{{ old('upline_percentage_commission', 0) }}" type="number" min="0" max="100" step="0.01" class="mt-1 w-full rounded-xl border-slate-200"></label><label class="text-sm">Cap<input name="upline_commission_cap" value="{{ old('upline_commission_cap', 1000) }}" type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-200"></label></div><label class="mt-4 flex gap-2 text-sm"><input name="commission_feature" value="1" type="checkbox" @checked(old('commission_feature', true))> Enable commission</label></details>

            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-slate-50 p-4"><div class="flex flex-wrap gap-5 text-sm">@foreach(['visibility'=>'Active','affiliate_visibility'=>'Visible to affiliates','public_visibility'=>'Publicly visible'] as $field=>$label)<input type="hidden" name="{{ $field }}" value="0"><label><input name="{{ $field }}" value="1" type="checkbox" @checked(old($field, false))> {{ $label }}</label>@endforeach</div><button class="rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white">Add product plan</button></div>
        </form>

        <form method="POST" action="{{ route('parent-admin.product-plans.bulk-store') }}" x-show="mode==='bulk'" x-data="bulkProductPlans(@js($categories), @js($connections), @js($levels))" class="mt-6 space-y-4">@csrf
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">Every row is validated before saving. If one row is invalid, no plan is created.</div>
            <template x-for="(row,i) in rows" :key="row.id"><article class="rounded-2xl border p-4"><div class="flex justify-between"><strong x-text="`Plan ${i+1}`"></strong><button type="button" x-show="rows.length>1" @click="rows.splice(i,1)" class="text-sm text-red-600">Remove</button></div><div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-4"><input :name="`plans[${i}][product_plan_name]`" required placeholder="Plan name" class="rounded-xl border-slate-200"><select :name="`plans[${i}][product_plan_category_id]`" required class="rounded-xl border-slate-200"><option value="">Category</option><template x-for="category in categories"><option :value="category.id" x-text="category.product_plan_category_name"></option></template></select><input :name="`plans[${i}][cost_price]`" required type="number" min="0" step=".01" placeholder="Provider cost" class="rounded-xl border-slate-200"><select :name="`plans[${i}][route][parent_provider_connection_id]`" class="rounded-xl border-slate-200"><option value="">Provider route</option><template x-for="connection in connections"><option :value="connection.id" x-text="connection.name"></option></template></select><input :name="`plans[${i}][route][provider_plan_id]`" placeholder="External plan ID" class="rounded-xl border-slate-200"><input :name="`plans[${i}][api_id]`" placeholder="Internal reference" class="rounded-xl border-slate-200"><input :name="`plans[${i}][data_size_in_mb]`" type="number" min="0" placeholder="Data MB" class="rounded-xl border-slate-200"><input :name="`plans[${i}][validity_in_days]`" type="number" min="0" placeholder="Validity days" class="rounded-xl border-slate-200"></div><input type="hidden" :name="`plans[${i}][profit_category]`" value="flat"><input type="hidden" :name="`plans[${i}][visibility]`" :value="row.visibility?1:0"><input type="hidden" :name="`plans[${i}][affiliate_visibility]`" :value="row.affiliate_visibility?1:0"><input type="hidden" :name="`plans[${i}][public_visibility]`" :value="row.public_visibility?1:0"><div class="mt-3 flex gap-4 text-xs"><label><input type="checkbox" x-model="row.visibility"> Active</label><label><input type="checkbox" x-model="row.affiliate_visibility"> Affiliates</label><label><input type="checkbox" x-model="row.public_visibility"> Public</label></div><div class="mt-3 grid gap-2 md:grid-cols-3"><template x-for="(level,j) in levels"><label class="text-xs"><span x-text="level.name"></span><input type="hidden" :name="`plans[${i}][prices][${j}][parent_reseller_level_id]`" :value="level.id"><input :name="`plans[${i}][prices][${j}][selling_price]`" type="number" min="0" step=".01" class="mt-1 w-full rounded-lg border-slate-200"></label></template></div></article></template>
            <div class="flex justify-between"><button type="button" @click="rows.push({id:crypto.randomUUID()})" class="rounded-xl border px-4 py-2">Add another row</button><button class="rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white">Create plans</button></div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-end justify-between gap-4 border-b p-4">
            <div><h2 class="font-semibold">Parent product plans</h2><p class="text-xs text-slate-500">{{ $plans->total() }} plans · {{ $pageSize === 'all' ? 'Showing all' : $pageSize.' per page' }}</p></div>
            <form method="GET" action="{{ route('parent-admin.product-plans.index') }}" class="flex flex-1 flex-wrap justify-end gap-2">
                <input name="search" value="{{ request('search') }}" placeholder="Search name, category or network" class="min-w-48 flex-1 rounded-lg border-slate-200 text-sm md:max-w-xs">
                <select name="category_id" class="max-w-56 rounded-lg border-slate-200 text-sm"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->product_plan_category_name }}</option>@endforeach</select>
                <select name="per_page" class="rounded-lg border-slate-200 text-sm" aria-label="Plans per page">@foreach($pageSizes as $size)<option value="{{ $size }}" @selected($pageSize === $size)>{{ $size === 'all' ? 'All plans' : $size.' per page' }}</option>@endforeach</select>
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filter</button>
                @if(request()->hasAny(['search','category_id']))<a href="{{ route('parent-admin.product-plans.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold">Clear</a>@endif
            </form>
        </div>

        <form method="POST" action="{{ route('parent-admin.product-plans.bulk-update') }}" @submit="if (!confirm(selectionScope === 'all' ? `Apply this action to all {{ $plans->total() }} matching plans?` : `Apply this action to ${selectedIds.length} selected plans?`)) $event.preventDefault()">@csrf @method('PATCH')
            <input type="hidden" name="selection_scope" :value="selectionScope">
            <input type="hidden" name="search" value="{{ request('search') }}">
            <input type="hidden" name="category_id" value="{{ request('category_id') }}">
            <div class="flex flex-wrap items-center gap-2 border-b bg-slate-50 px-4 py-2.5">
                <button type="button" class="text-xs font-semibold text-blue-700" @click="selectionScope='selected'; selectedIds = @js($plans->pluck('id')->map(fn($id)=>(string)$id)->values())">Select this page</button>
                <button type="button" class="text-xs font-semibold text-blue-700" @click="selectionScope='all'; selectedIds=[]">Select all {{ $plans->total() }}{{ request()->hasAny(['search','category_id']) ? ' filtered plans' : ' plans' }}</button>
                <button type="button" class="text-xs font-semibold text-slate-500" @click="selectionScope='selected'; selectedIds = []">Clear selection</button>
                <span class="text-xs text-slate-400" x-text="selectionScope === 'all' ? 'All {{ $plans->total() }} matching plans selected' : `${selectedIds.length} selected`"></span>
                <div class="ml-auto flex flex-wrap gap-2">
                    <select name="action" required class="rounded-lg border-slate-200 py-1.5 text-xs"><option value="">Bulk action</option><option value="activate">Activate</option><option value="deactivate">Deactivate</option><option value="show_affiliates">Show for affiliates</option><option value="hide_affiliates">Hide from affiliates</option><option value="show_public">Show publicly</option><option value="hide_public">Hide publicly</option></select>
                    <button :disabled="selectionScope === 'selected' && selectedIds.length === 0" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">Apply</button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[850px] text-left text-sm">
                    <thead class="bg-white text-[10px] uppercase tracking-wider text-slate-400"><tr><th class="w-10 px-3 py-2"></th><th class="px-3 py-2">Plan</th><th class="px-3 py-2">Category</th><th class="px-3 py-2">Cost</th><th class="px-3 py-2">Route</th><th class="px-3 py-2">Status</th><th class="w-20 px-3 py-2"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($plans as $plan)
                        @php
                            $primaryRoute = $plan->providerRoutes->firstWhere('priority', 1);
                            $drawerPlan = [
                                'id' => $plan->id,
                                'product_plan_name' => $plan->product_plan_name,
                                'product_plan_category_id' => $plan->product_plan_category_id,
                                'api_id' => $plan->api_id,
                                'cost_price' => $plan->cost_price,
                                'admin_cost_price' => $plan->admin_cost_price,
                                'data_size_in_mb' => $plan->data_size_in_mb,
                                'validity_in_days' => $plan->validity_in_days,
                                'profit_category' => $plan->profit_category ?: 'flat',
                                'visibility' => (bool) $plan->visibility,
                                'affiliate_visibility' => (bool) $plan->affiliate_visibility,
                                'public_visibility' => (bool) $plan->public_visibility,
                                'commission_feature' => (int) $plan->commission_feature,
                                'upline_commission_option' => $plan->upline_commission_option ?: 'flat',
                                'upline_flat_commission' => $plan->upline_flat_commission ?: 0,
                                'upline_percentage_commission' => $plan->upline_percentage_commission ?: 0,
                                'upline_commission_cap' => $plan->upline_commission_cap ?: 1000,
                                'route' => ['parent_provider_connection_id' => $primaryRoute?->parent_provider_connection_id, 'provider_plan_id' => $primaryRoute?->provider_plan_id],
                                'prices' => $levels->map(fn($level) => ['parent_reseller_level_id' => $level->id, 'name' => $level->name, 'selling_price' => $plan->parentPrices->firstWhere('parent_reseller_level_id', $level->id)?->selling_price, 'max_profit' => $plan->parentPrices->firstWhere('parent_reseller_level_id', $level->id)?->max_profit])->values(),
                                'configuration_url' => route('parent-admin.product-plans.configuration.update', $plan),
                                'edit_url' => route('parent-admin.product-plans.edit', $plan),
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-3 py-2"><input type="checkbox" name="plan_ids[]" value="{{ $plan->id }}" x-model="selectedIds" @change="selectionScope='selected'" class="rounded border-slate-300"></td>
                            <td class="max-w-64 px-3 py-2"><p class="truncate text-xs font-semibold text-slate-900" title="{{ $plan->product_plan_name }}">{{ $plan->product_plan_name }}</p><p class="truncate text-[10px] text-slate-400">{{ $plan->api_id ?: 'No internal reference' }}</p></td>
                            <td class="max-w-48 px-3 py-2"><p class="truncate text-xs text-slate-600" title="{{ $plan->product_plan_category?->product_plan_category_name }}">{{ $plan->product_plan_category?->product_plan_category_name ?: '—' }}</p></td>
                            <td class="whitespace-nowrap px-3 py-2 text-xs font-semibold">₦{{ number_format((float)$plan->cost_price, 2) }}</td>
                            <td class="max-w-44 px-3 py-2"><p class="truncate text-xs text-slate-600" title="{{ $primaryRoute?->provider_plan_id }}">{{ $primaryRoute?->provider_plan_id ?: 'Draft — no route' }}</p><p class="truncate text-[10px] text-slate-400">{{ $primaryRoute?->parentProviderConnection?->name }}</p></td>
                            <td class="px-3 py-2"><div class="flex flex-wrap gap-1">@foreach(['visibility'=>'Active','affiliate_visibility'=>'Affiliates','public_visibility'=>'Public'] as $field=>$label)<span class="rounded-full px-2 py-0.5 text-[9px] font-bold {{ $plan->{$field} ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-400' }}">{{ $label }}</span>@endforeach</div></td>
                            <td class="px-3 py-2 text-right"><button type="button" data-testid="open-plan-drawer-{{ $plan->id }}" data-plan="{{ json_encode($drawerPlan) }}" data-update-url="{{ route('parent-admin.product-plans.configuration.update', $plan) }}" data-edit-url="{{ route('parent-admin.product-plans.edit', $plan) }}" @click="openDrawer(JSON.parse($el.dataset.plan))" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">Edit</button></td>
                        </tr>
                    @empty<tr><td colspan="7" class="p-8 text-center text-slate-500">No product plans found.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </form>
        <div class="border-t p-3">{{ $plans->links() }}</div>
    </section>

    <div x-cloak x-show="drawerOpen" x-transition.opacity class="fixed inset-0 z-[70] bg-slate-950/50" @click="closeDrawer()"></div>
    <aside data-testid="product-plan-drawer" x-cloak :class="drawerOpen ? 'translate-x-0' : 'translate-x-full'" class="fixed inset-y-0 right-0 z-[80] flex w-full max-w-2xl flex-col bg-slate-50 shadow-2xl transition-transform duration-200" role="dialog" aria-modal="true" aria-label="Edit product plan">
        <div class="flex items-center justify-between border-b bg-white px-5 py-4"><div class="min-w-0"><p class="text-[10px] font-bold uppercase tracking-wider text-blue-600">Full configuration editor</p><h2 class="truncate font-semibold" x-text="selectedPlan?.product_plan_name || 'Product plan'"></h2></div><button type="button" @click="closeDrawer()" class="rounded-lg border px-3 py-2 text-sm">Close</button></div>
        <form x-show="selectedPlan" :action="selectedPlan?.configuration_url" method="POST" class="flex min-h-0 flex-1 flex-col">@csrf @method('PUT')
            <input type="hidden" name="editing_plan_id" :value="selectedPlan?.id">
            <div class="flex-1 space-y-5 overflow-y-auto p-5">
                <details class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-950"><summary class="cursor-pointer font-semibold">Airtime setup guide</summary><p class="mt-2">For airtime, enter costs and reseller prices per ₦1,000 face value and use Percentage mode. Fixed-value data and cable plans use their actual naira costs.</p></details>
                <section class="rounded-xl border bg-white p-4"><h3 class="text-sm font-semibold">Plan details</h3><div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <label class="text-xs">Name<input name="product_plan_name" x-model="selectedPlan.product_plan_name" required class="mt-1 w-full rounded-lg border-slate-200 text-sm"></label>
                    <label class="text-xs">Category<select name="product_plan_category_id" x-model.number="selectedPlan.product_plan_category_id" required class="mt-1 w-full rounded-lg border-slate-200 text-sm">@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->product_plan_category_name }}</option>@endforeach</select></label>
                    <label class="text-xs">Internal reference<input name="api_id" x-model="selectedPlan.api_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></label>
                    <label class="text-xs">Provider cost<input name="cost_price" x-model="selectedPlan.cost_price" required type="number" min="0" step=".01" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></label>
                    <label class="text-xs">Admin/reference cost<input name="admin_cost_price" x-model="selectedPlan.admin_cost_price" type="number" min="0" step=".01" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></label>
                    <label class="text-xs">Data size MB<input name="data_size_in_mb" x-model="selectedPlan.data_size_in_mb" type="number" min="0" step=".01" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></label>
                    <label class="text-xs">Validity days<input name="validity_in_days" x-model="selectedPlan.validity_in_days" type="number" min="0" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></label>
                    <label class="text-xs">Profit mode<select name="profit_category" x-model="selectedPlan.profit_category" class="mt-1 w-full rounded-lg border-slate-200 text-sm"><option value="flat">Flat</option><option value="percent">Percentage</option></select></label>
                </div></section>
                <section class="rounded-xl border bg-white p-4"><h3 class="text-sm font-semibold">Primary provider route</h3><div class="mt-3 grid gap-3 sm:grid-cols-2"><label class="text-xs">Connection<select name="route[parent_provider_connection_id]" x-model="selectedPlan.route.parent_provider_connection_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm"><option value="">No route</option>@foreach($connections as $connection)<option value="{{ $connection->id }}">{{ $connection->name }}</option>@endforeach</select></label><label class="text-xs">Provider external plan ID<input name="route[provider_plan_id]" x-model="selectedPlan.route.provider_plan_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></label></div></section>
                <section class="rounded-xl border bg-white p-4"><h3 class="text-sm font-semibold">Reseller prices</h3><div class="mt-3 grid gap-3 sm:grid-cols-2"><template x-for="(price,index) in selectedPlan?.prices || []" :key="price.parent_reseller_level_id"><div class="rounded-lg bg-slate-50 p-3"><strong class="text-xs" x-text="price.name"></strong><input type="hidden" :name="`prices[${index}][parent_reseller_level_id]`" :value="price.parent_reseller_level_id"><div class="mt-2 grid grid-cols-2 gap-2"><label class="text-[10px]">Selling price<input :name="`prices[${index}][selling_price]`" x-model="price.selling_price" type="number" min="0" step=".01" class="mt-1 w-full rounded-lg border-slate-200 text-xs"></label><label class="text-[10px]">Maximum profit<input :name="`prices[${index}][max_profit]`" x-model="price.max_profit" type="number" min="0" step=".01" class="mt-1 w-full rounded-lg border-slate-200 text-xs"></label></div></div></template></div></section>
                <input type="hidden" name="commission_feature" :value="selectedPlan?.commission_feature || 0"><input type="hidden" name="upline_commission_option" :value="selectedPlan?.upline_commission_option || 'flat'"><input type="hidden" name="upline_flat_commission" :value="selectedPlan?.upline_flat_commission || 0"><input type="hidden" name="upline_percentage_commission" :value="selectedPlan?.upline_percentage_commission || 0"><input type="hidden" name="upline_commission_cap" :value="selectedPlan?.upline_commission_cap || 1000">
                <section class="rounded-xl border bg-white p-4"><div class="flex flex-wrap gap-4 text-xs">@foreach(['visibility'=>'Active','affiliate_visibility'=>'Affiliates','public_visibility'=>'Public'] as $field=>$label)<input type="hidden" name="{{ $field }}" value="0"><label><input name="{{ $field }}" value="1" type="checkbox" x-model="selectedPlan.{{ $field }}"> {{ $label }}</label>@endforeach</div></section>
            </div>
            <div class="flex items-center justify-between gap-3 border-t bg-white p-4"><a :href="selectedPlan?.edit_url" class="text-xs font-semibold text-slate-500">Open fallback page</a><button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Save full configuration</button></div>
        </form>
    </aside>
</div>
@endsection

@push('scripts')
<script>document.addEventListener('alpine:init',()=>Alpine.data('bulkProductPlans',(categories,connections,levels)=>({categories,connections,levels,rows:[{id:crypto.randomUUID(),visibility:false,affiliate_visibility:false,public_visibility:false}]})))</script>
@endpush
