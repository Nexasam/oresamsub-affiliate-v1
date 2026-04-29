@extends('layouts.app')
@section('content')

<!-- Start::main-content -->
<div class="main-content px-4 py-6 bg-gray-100 dark:bg-gray-900 min-h-screen">

    <!-- Breadcrumb -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
       
        <div class="flex justify-between items-center space-x-2 p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
            <h5 class="text-gray-700 dark:text-gray-200 font-medium text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 4h14v2H3V4zm0 4h14v2H3V8zm0 4h14v2H3v-2zm0 4h14v2H3v-2z"/>
                </svg>
                Product Plan Categories
            </h5>
        
            {{-- 🔹 Generate Categories Button --}}
            <button 
                id="generateCategoriesBtn"
                class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-2 rounded transition"
                onclick="generateAffiliateCategories()"
            >
                ⚙️ Generate Categories
            </button>
        </div>
        

    </div>

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
                <ul class="list-disc pl-5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Product Plan Categories -->
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">

                <!-- Card Header -->
                <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <h5 class="text-gray-700 dark:text-gray-200 font-medium text-sm flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4h14v2H3V4zm0 4h14v2H3V8zm0 4h14v2H3v-2zm0 4h14v2H3v-2z"/>
                        </svg>
                        Product Plan Categories
                    </h5>
                </div>

                <!-- Table -->
                <div class="p-4 overflow-auto text-xs bg-gray-50 dark:bg-gray-900">
                    <table id="plan_categories_table" class="w-full min-w-[900px] border-collapse">
                        <thead class="bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-300 uppercase text-[11px] tracking-wide">
                            <tr>
                                <th class="px-0.5 py-0.5 text-left font-semibold">#</th>
                                <th class="px-0.5 py-0.5 text-left font-semibold">Category</th>
                                <th class="px-0.5 py-0.5 text-left font-semibold">Product</th>
                                <th class="px-0.5 py-0.5 text-left font-semibold">Network</th>
                                <th class="px-0.5 py-0.5 text-left font-semibold">Date Added</th>
                                {{-- <th class="px-0.5 py-0.5 text-left font-semibold">Visibility</th> --}}
                                {{-- <th class="px-0.5 py-0.5 text-left font-semibold">Action</th> --}}
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($product_plan_categories as $index => $category)
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors text-xs text-gray-700 dark:text-gray-300">
                                <td class="px-0.5 py-0.5">{{ $index + 1 }} </td>
                                <td class="px-0.5 py-0.5">{{ $category->product_plan_category_name ?? 'nil' }}</td>
                                <td class="px-0.5 py-0.5">{{ $category->product->product_name ?? 'nil' }}</td>
                                <td class="px-0.5 py-0.5">{{ $category->network->network_name ?? 'nil' }}</td>
                                <td class="px-0.5 py-0.5">{{ $category->created_at->format('d M, Y') }}</td>
                                {{-- <td class="px-0.5 py-0.5">{{ $category->visibility ? 'Public' : 'Private' }}</td> --}}
                                {{-- <td class="px-0.5 py-0.5">
                                    @if ($category->product)
                                        <a href="{{ route('admin.bulk_data_plans.index', $category->id) }}"
                                           class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-[11px]">
                                            Manage
                                        </a>
                                    @else
                                        <span class="italic text-gray-500 dark:text-gray-400 text-[11px]">Not applicable</span>
                                    @endif
                                </td> --}}
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                

            </div>
        </div>
    </div>

</div>
@endsection


<script>
    function generateAffiliateCategories() {
    const btn = document.getElementById("generateCategoriesBtn");
    if (!btn) return;

    // ✅ Simple confirmation
    if (!confirm("Are you sure you want to generate your categories?")) {
        return;
    }

    btn.disabled = true;
    btn.innerText = "⏳ Generating...";

    fetch("{{ route('admin.affiliate.generateCategories') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
    })
    .then(res => res.json())
    .then(data => {
        if (data.status) {
            btn.innerText = "✅ Categories Generated";
            Swal.fire({
                icon: "success",
                title: "Done!",
                text: data.message || "Categories generated successfully.",
                timer: 2000,
                showConfirmButton: false,
            });
            setTimeout(() => location.reload(), 1500);
        } else {
            btn.innerText = "⚠️ Try Again";
            Swal.fire({
                icon: "warning",
                title: "Oops!",
                text: data.message || "Something went wrong while generating.",
            });
        }
    })
    .catch((err) => {
        console.error(err);
        btn.innerText = "❌ Error!";
        Swal.fire({
            icon: "error",
            title: "Server Error",
            text: "Could not connect to server.",
        });
    })
    .finally(() => {
        btn.disabled = false;
    });
}
   


    function addAffiliateCategory(categoryId, categoryName) {
    const btn = document.querySelector(`button[data-id='${categoryId}']`);
    if (!btn) return;
    btn.disabled = true;
    btn.innerText = "Adding...";

    fetch("{{ route('admin.affiliate.addCategory') }}", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
        body: JSON.stringify({ plan_category_id: categoryId, plan_category_name: categoryName })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === true) {
            btn.classList.add("bg-green-600");
            btn.innerText = "Added ✓";
        } else if (data.status === "exists") {
            btn.innerText = "Already Added";
        } else {
            btn.innerText = "Failed!";
        }
    })
    .catch(() => btn.innerText = "Error!")
    .finally(() => setTimeout(() => $('#plan_categories_table').DataTable().ajax.reload(null, false), 1000));
}
</script>
