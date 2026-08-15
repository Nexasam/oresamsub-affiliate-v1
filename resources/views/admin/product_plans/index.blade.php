@extends('layouts.app')
@section('content')

@if(config('parent_businesses.features.affiliate_blade_ui'))
<div class="workspace-page" x-data="affiliatePlansTable()" x-init="load()">
    <div class="workspace-stack">
        <x-workspace.page-header title="Product plans" description="Review acquisition prices, customer margins and plan availability.">
            <button type="button" class="workspace-btn-success" @click="sync()" :disabled="syncing"><span x-text="syncing ? 'Syncing…' : 'Sync plans'"></span></button>
        </x-workspace.page-header>
        @if(Session::has('success'))<x-workspace.alert type="success">{{ Session::get('success') }}</x-workspace.alert>@endif
        @if(Session::has('failure'))<x-workspace.alert type="error">{{ Session::get('failure') }}</x-workspace.alert>@endif
        @if($errors->any())<x-workspace.alert type="error"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-workspace.alert>@endif

        <section class="workspace-panel">
            <div class="workspace-panel-header">
                <div><h3 class="font-semibold">Affiliate catalogue</h3><p class="mt-1 text-xs text-slate-500" x-text="`${filtered.length} plans`"></p></div>
                <div class="grid w-full gap-2 sm:w-auto sm:grid-cols-3">
                    <input x-model.debounce.250ms="search" @input="page=1" class="workspace-input sm:w-64" placeholder="Search plan, network or category">
                    <select x-model="availability" @change="page=1" class="workspace-input"><option value="">All plans</option><option value="available">Available</option><option value="not available">Unavailable</option></select>
                    <select x-model.number="perPage" @change="page=1" class="workspace-input"><option :value="10">10 rows</option><option :value="25">25 rows</option><option :value="50">50 rows</option></select>
                </div>
            </div>
            <div x-show="notice" class="p-4"><x-workspace.alert type="success"><span x-text="notice"></span></x-workspace.alert></div>
            <div x-show="error" class="p-4"><x-workspace.alert type="error"><span x-text="error"></span></x-workspace.alert></div>
            <div class="workspace-table-wrap">
                <table class="workspace-table min-w-[1050px]">
                    <thead><tr><th>Plan</th><th>Category</th><th>Acquisition</th><th>Maximum profit</th><th>Customer margins</th><th>Availability</th><th>Actions</th></tr></thead>
                    <tbody>
                        <template x-for="row in visible" :key="row.DT_RowIndex"><tr :class="row.DT_RowClass || ''">
                            <td><p class="font-semibold" x-text="row.product_plan_name"></p><p class="mt-1 text-xs text-slate-500" x-text="row.network_name"></p></td>
                            <td><p x-text="row.category"></p><p class="mt-1 text-xs text-slate-500" x-text="row.data_size_in_mb + ' · ' + row.validity_in_days"></p></td>
                            <td><div x-html="row.cost_price"></div></td>
                            <td><div x-html="row.max_profit_range"></div></td>
                            <td><div x-html="row.user_plan_profit"></div></td>
                            <td><div class="space-y-2"><div x-html="row.admin_visibility"></div><div x-html="row.affiliate_visibility"></div><div x-html="row.public_visibility"></div></div></td>
                            <td><div x-html="row.action"></div></td>
                        </tr></template>
                        <tr x-show="!loading && visible.length === 0"><td colspan="7" class="workspace-empty">No plans match your filters.</td></tr>
                        <tr x-show="loading"><td colspan="7" class="workspace-empty">Loading product plans…</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-slate-200 px-4 py-3 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs text-slate-500" x-text="pageSummary"></p><div class="flex gap-2"><button class="workspace-btn-secondary" @click="page--" :disabled="page<=1">Previous</button><button class="workspace-btn-secondary" @click="page++" :disabled="page>=pages">Next</button></div></div>
        </section>
    </div>
</div>
@else

<!-- Start::main-content -->
<div class="main-content px-4 py-6 bg-gray-100 dark:bg-gray-900 min-h-screen">

    <!-- Page Header / Breadcrumb -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
        <div class="flex items-center space-x-2">
            <!-- Home SVG -->
            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 2L2 9h3v9h4V13h2v5h4V9h3L10 2z"/>
            </svg>
            <nav class="text-sm text-gray-500 dark:text-gray-400" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li>
                        <a href="{{ route('dashboard') }}" class="hover:text-blue-500 dark:hover:text-blue-400">Dashboard</a>
                    </li>
                    <li>
                        <span class="mx-1 text-gray-400 dark:text-gray-500">/</span>
                        <span class="text-gray-700 dark:text-gray-200">Product Plans</span>
                    </li>

                  
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header / Breadcrumb End -->

    <!-- Alerts -->
    <div class="col-span-12 mb-4">
        @if (Session::has('success'))
            <div class="bg-green-100 text-green-700 p-3 rounded text-xs">{{ Session::get('success') }}</div>
        @endif

        @if (Session::has('failure'))
            <div class="bg-red-100 text-red-700 p-3 rounded text-xs">{{ Session::get('failure') }}</div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-3 rounded text-xs">
                <ul class="list-disc ml-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Product Plans Card -->
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">

                <!-- Card Header -->
                <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="text-gray-700 dark:text-gray-200 font-medium text-lg flex items-center gap-2">
                        <!-- Icon -->
                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 3h12v2H4V3zm0 4h12v2H4V7zm0 4h12v2H4v-2zm0 4h12v2H4v-2z"/>
                        </svg>
                        Product Plans
                    </h5>
                         {{-- 🔹 Generate Categories Button --}}
                    <button 
                         id="generatePlansBtn"
                         class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-2 rounded transition"
                         onclick="syncAffiliatePlans()"
                     >
                      ⚙️ Sync Plans
                  </button>
                </div>

                <!-- Card Body -->
                <div class="p-4 overflow-auto text-xs bg-gray-50 dark:bg-gray-900">
                    <table id="product_plans_table" class="w-full border-collapse min-w-[1000px]">
                        <thead class="bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-300 uppercase text-[11px] tracking-wide">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">#</th>
                                <th class="px-3 py-2 text-left font-semibold">Product</th>
                                <th class="px-3 py-2 text-left font-semibold">Network</th>
                                <th class="px-3 py-2 text-left font-semibold">Plan Name</th>
                                <th class="px-3 py-2 text-left font-semibold">Category</th>
                                <th class="px-3 py-2 text-left font-semibold">Data Size</th>
                                <th class="px-3 py-2 text-left font-semibold">Validity</th>
                                <th class="px-3 py-2 text-left font-semibold">Affiliate Acquisition Price</th>
                                {{-- <th class="px-3 py-2 text-left font-semibold">Admin Cost</th> --}}
                                <th class="px-3 py-2 text-left font-semibold">Max Profit</th>
                                <th class="px-3 py-2 text-left font-semibold">User Plan Margin</th>
                                <th class="px-3 py-2 text-left font-semibold">Admin</th>
                                <th class="px-3 py-2 text-left font-semibold">Visibility</th>
                                <th class="px-3 py-2 text-left font-semibold">Created</th>
                                <th class="px-3 py-2 text-left font-semibold">Updated</th>
                                <th class="px-3 py-2 text-left font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-200 text-[12px]">
                            {{-- Populated via DataTable / AJAX --}}
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
    <!-- Product Plans Card End -->

</div>
<!-- End::main-content -->
@endif
@endsection
<script>
    function affiliatePlansTable() {
      return {
        rows: [], loading: false, syncing: false, error: '', notice: '', search: '', availability: '', page: 1, perPage: 25,
        async load() { this.loading = true; this.error = ''; try { const response = await fetch(@js(route('admin.product_plans.admin_fetch_product_plans')), {headers:{'Accept':'application/json'}}); if (!response.ok) throw new Error('Product plans could not be loaded.'); const payload = await response.json(); this.rows = payload.data || []; } catch (error) { this.error = error.message; } finally { this.loading = false; } },
        plain(value) { const el = document.createElement('div'); el.innerHTML = value || ''; return (el.textContent || '').toLowerCase(); },
        get filtered() { const term = this.search.toLowerCase().trim(); return this.rows.filter(row => (!term || `${row.product_plan_name} ${row.network_name} ${row.category}`.toLowerCase().includes(term)) && (!this.availability || this.plain(row.affiliate_visibility).includes(this.availability))); },
        get pages() { return Math.max(1, Math.ceil(this.filtered.length / this.perPage)); },
        get visible() { if (this.page > this.pages) this.page = this.pages; const start=(this.page-1)*this.perPage; return this.filtered.slice(start,start+this.perPage); },
        get pageSummary() { if (!this.filtered.length) return 'No plans'; const start=(this.page-1)*this.perPage+1; return `Showing ${start}–${Math.min(start+this.perPage-1,this.filtered.length)} of ${this.filtered.length}`; },
        async sync() { if (!confirm('Sync product plans from your parent catalogue?')) return; this.syncing=true; this.error=''; try { const response=await fetch(@js(route('admin.sync_affiliate_product_plans')),{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':@js(csrf_token())}}); const payload=await response.json(); if (!response.ok || !(payload.status === 1 || payload.status === true)) throw new Error(payload.message || 'Plan sync failed.'); this.notice=payload.message || 'Plans synchronized successfully.'; await this.load(); } catch(error) { this.error=error.message; } finally { this.syncing=false; } }
      }
    }

    document.addEventListener('alpine:init', () => {
      Alpine.data('profitEditorComponent', () => ({
        open: false,
        saving: false,
        success: false,
        profits: {},
        planId: null,
        disabled: false,
    
        init() {
          // this.$el is the wrapper div returned by controller
          const el = this.$el;
          try {
            const raw = el.getAttribute('data-profits') || '{}';
            this.profits = JSON.parse(raw);
          } catch (e) {
            console.error('Invalid profits JSON', e);
            this.profits = {};
          }
          this.planId = el.getAttribute('data-plan-id') || null;
          this.disabled = el.getAttribute('data-disabled') === '1';
    
          if (this.disabled) {
            // optionally disable entire widget
            // e.g., hide toggle or show disabled state
          }
        },
    
        async updatePlanProfit() {
          if (this.disabled) return;
          if (!this.planId) return console.error('Missing planId');
    
          this.saving = true;
          this.success = false;
    
          try {
            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrf = tokenMeta ? tokenMeta.getAttribute('content') : null;
    
            const res = await fetch('/admin/update_affiliate_product_profits', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf || ''
              },
              body: JSON.stringify({
                plan_id: this.planId,
                profits: this.profits
              })
            });
    
            const data = await res.json();
            if (res.ok && data.status === true) {
              this.success = true;
              // close after short delay and show success
              setTimeout(() => {
                this.success = false;
                this.open = false; // auto-close for neatness
              }, 1200);
            } else {
              console.error('Save failed', data);
              const validationMessage = Object.values(data.errors || {}).flat()[0];
              alert(validationMessage || data.message || 'Failed to save profits');
            }
          } catch (err) {
            console.error(err);
            alert('Error saving profits');
          } finally {
            this.saving = false;
          }
        }
      }));
    });


    function syncAffiliatePlans() {
          const btn = document.getElementById("generatePlansBtn");
          if (!btn) return;

          // ✅ Simple confirmation before proceeding
          if (!confirm("Are you sure you want to sync your product plans?")) {
              return;
          }

          btn.disabled = true;
          btn.innerText = "⏳ Syncing...";

          fetch("{{ route('admin.sync_affiliate_product_plans') }}", {
              method: "POST",
              headers: {
                  "Content-Type": "application/json",
                  "X-CSRF-TOKEN": "{{ csrf_token() }}"
              },
              // Optional: include affiliate_id if required
              // body: JSON.stringify({ affiliate_id: "{{ $affiliateId ?? auth()->id() }}" })
          })
          .then(async (res) => {
              // Handle possible non-JSON (HTML error) responses
              const text = await res.text();
              try {
                  return JSON.parse(text);
              } catch (err) {
                  console.error("Invalid JSON response:", text);
                  throw new Error("Invalid server response");
              }
          })
          .then((data) => {
              if (data.status === 1 || data.status === true) {
                  btn.innerText = "✅ Synced Successfully";
                  Swal.fire({
                      icon: "success",
                      title: "Plans Synced!",
                      text: data.message || "Product plans synced successfully.",
                      timer: 2000,
                      showConfirmButton: false,
                  });
                  setTimeout(() => location.reload(), 1500);
              } else {
                  btn.innerText = "⚠️ Try Again";
                  Swal.fire({
                      icon: "warning",
                      title: "Sync Failed",
                      text: data.message || "Something went wrong while syncing plans.",
                  });
              }
          })
          .catch((err) => {
              console.error(err);
              btn.innerText = "❌ Error!";
              Swal.fire({
                  icon: "error",
                  title: "Server Error",
                  text: "Could not connect to server or invalid response received.",
              });
          })
          .finally(() => {
              btn.disabled = false;
          });
      }

</script>


  
