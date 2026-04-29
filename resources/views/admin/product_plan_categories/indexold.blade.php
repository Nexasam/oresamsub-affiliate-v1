@extends('layouts.app')
@section('content')

<!-- Start::main-content -->
<div class="main-content px-4 py-6">

    <!-- Page Header / Breadcrumb -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
        <div class="flex items-center space-x-2">
            <!-- Home SVG -->
            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 2L2 9h3v9h4V13h2v5h4V9h3L10 2z"/>
            </svg>
            <nav class="text-xs text-gray-500 dark:text-gray-400" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li>
                        <a href="{{ route('dashboard') }}" class="hover:text-blue-500 dark:hover:text-blue-400">Dashboard</a>
                    </li>
                    <li>
                        <span class="mx-1 text-gray-400 dark:text-gray-500">/</span>
                        <span class="text-gray-700 dark:text-gray-200">Product Plan Categories</span>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header / Breadcrumb End -->

    <!-- Notifications -->
    <div class="col-span-12 mb-4">
        @if (Session::has('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded">{{ Session::get('success') }}</div>
        @endif

        @if (Session::has('failure'))
        <div class="bg-red-100 text-red-700 p-3 rounded">{{ Session::get('failure') }}</div>
        @endif

        @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>

    <!-- Product Plan Categories Card -->
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">

                <!-- Card Header with Tabs -->
                <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="text-gray-700 dark:text-gray-200 font-medium text-lg flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4h14v2H3V4zm0 4h14v2H3V8zm0 4h14v2H3v-2zm0 4h14v2H3v-2z"/>
                        </svg>
                        Product Plan Categories
                    </h5>

                    {{-- <div class="flex space-x-2"> --}}
                        {{-- <button x-data @click="$dispatch('tab-switch', 'view')" class="ti-btn ti-btn-outline">View Categories</button> --}}
                        {{-- <button x-data @click="$dispatch('tab-switch', 'create')" class="ti-btn ti-btn-primary">Create Category</button> --}}
                    {{-- </div> --}}

                </div>

                <!-- Card Body -->
                <div class="p-4 overflow-auto text-xs" x-data="{ tab: 'view' }" @tab-switch.window="tab = $event.detail">

                    <!-- View Categories Tab -->
                    <div  x-show="tab === 'view'" x-transition>
                        <table id="plan_categories_table" class="ti-custom-table ti-custom-table-head w-full min-w-[900px]">
                            <thead class="bg-gray-50 dark:bg-black/20">
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                    <th>Product</th>
                                    <th>Network</th>
                                    <th>Date Added</th>
                                    {{-- <th>Hot Sales</th> --}}
                                    <th>Visibility</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($product_plan_categories as $index => $category)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $category->product_plan_category_name ?? 'nil' }}</td>
                                    <td>{{ $category->product->product_name ?? 'nil' }}</td>
                                    <td>{{ $category->network->network_name ?? 'nil' }}</td>
                                    <td>{{ $category->created_at->format('d M, Y') }}</td>
                                    <td>
                                        @if ($category->product)
                                            <a href="{{ route('admin.bulk_data_plans.index',$category->id) }}" class="ti-btn ti-btn-primary">Manage Bulk Plans</a>
                                        @else
                                            <i>Not applicable</i>
                                        @endif
                                    </td>
                                    <td>{{ $category->visibility ? 'Public' : 'Private' }}</td>
                                    <td>
                                        <!-- Actions like Edit/Delete -->
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Create Category Tab -->
                    {{-- <div x-show="tab === 'create'" x-transition class="mt-4">
                        <form method="POST" action="{{ route('admin.product_plan_categories.store') }}">
                            @csrf
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                                <div class="space-y-2">
                                    <label class="ti-form-label">Category Name</label>
                                    <input type="text" required name="product_plan_category_name" class="ti-form-input" placeholder="Enter category name">
                                </div>

                                <div class="space-y-2">
                                    <label class="ti-form-label">Choose Product</label>
                                    <select name="product_id" required class="ti-form-select">
                                        <option value="">Select</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->product_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label class="ti-form-label">Choose Network (Optional)</label>
                                    <select name="network_id" class="ti-form-select">
                                        <option value="">Select</option>
                                        @foreach ($networks as $network)
                                            <option value="{{ $network->id }}">{{ $network->network_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label class="ti-form-label">Choose Automation</label>
                                    <select name="automation_id" required class="ti-form-select">
                                        <option value="">Select</option>
                                        @foreach ($automations as $automation)
                                            <option value="{{ $automation->id }}">{{ $automation->automation_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="space-y-2 col-span-full">
                                    <button type="submit" class="ti-btn ti-btn-primary w-full">Create Product Plan Category</button>
                                </div>

                            </div>
                        </form>
                    </div> --}}

                </div>

            </div>
        </div>
    </div>
    <!-- Product Plan Categories Card End -->

</div>
<!-- End::main-content -->

@endsection


<script>
  function addAffiliateCategory(categoryId,categoryName) {
      const btn = document.querySelector(`button[data-id='${categoryId}']`);
      if (!btn) return;
  
      btn.disabled = true;
      btn.innerText = "Adding...";

      // alert(categoryId+' '+categoryName);
  
      fetch("{{ route('admin.affiliate.addCategory') }}", {
          method: "POST",
          headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": "{{ csrf_token() }}"
          },
          body: JSON.stringify({ plan_category_id: categoryId, plan_category_name: categoryName })
      })
      .then(res => res.json())
      .then(data => {
          if (data.status === 1) {
              btn.classList.remove("ti-btn-secondary");
              btn.classList.add("ti-btn-success");
              btn.innerText = "Added ✓";
              // reload table row or force DataTable refresh
              $('#plan_categories_table').DataTable().ajax.reload(null, false);
          } else if (data.status === "exists") {
              btn.innerText = "Already Added";
          } else {
              btn.innerText = "Failed!";
          }
      })
      .catch(err => {
          console.error(err);
          btn.innerText = "Error!";
      })
      .finally(() => {
          setTimeout(() => {
              $('#plan_categories_table').DataTable().ajax.reload(null, false);
          }, 1000);
      });
  }
  </script>
  