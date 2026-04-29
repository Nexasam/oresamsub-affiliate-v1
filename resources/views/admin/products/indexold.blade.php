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
            <nav class="text-sm text-gray-500 dark:text-gray-400" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li>
                        <a href="{{ route('dashboard') }}" class="hover:text-blue-500 dark:hover:text-blue-400">Dashboard</a>
                    </li>
                    <li>
                        <span class="mx-1 text-gray-400 dark:text-gray-500">/</span>
                        <span class="text-gray-700 dark:text-gray-200">Products</span>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header / Breadcrumb End -->

    <!-- Products Card -->
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">

                <!-- Card Header -->
                <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="text-gray-700 dark:text-gray-200 font-medium text-lg flex items-center gap-2">
                        <!-- Products SVG -->
                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 3h12v2H4V3zm0 4h12v2H4V7zm0 4h12v2H4v-2zm0 4h12v2H4v-2z"/>
                        </svg>
                        Main Products
                    </h5>
                    {{-- <a href="{{ route('admin.products.create') }}" class="ti-btn ti-btn-primary flex items-center gap-2">
                        <!-- Plus SVG -->
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Product
                    </a> --}}
                </div>

                <!-- Card Body -->
                <div class="p-4 overflow-auto">
                    @if (Session::has('success'))
                        <div class="bg-green-100 text-green-700 p-3 mb-4 rounded">{{ Session::get('success') }}</div>
                    @endif
                    @if (Session::has('failure'))
                        <div class="bg-red-100 text-red-700 p-3 mb-4 rounded">{{ Session::get('failure') }}</div>
                    @endif

                    <table class="ti-custom-table ti-custom-table-head ti-striped-table w-full min-w-[800px]">
                        <thead class="bg-gray-50 dark:bg-black/20">
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Activation Status</th>
                                <th>Visibility</th>
                                <th>Date Added</th>
                               
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $index => $product)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $product->product_name }}</td>
                                <td>
                                    @if ($product->active_status)
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-semibold">ACTIVE</span>
                                    @else
                                        <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-full text-xs font-semibold">INACTIVE</span>
                                    @endif
                                </td>
                                <td>{{ $product->visibility == 1 ? 'PUBLIC' : 'PRIVATE' }}</td>
                                <td>{{ $product->created_at->format('d M, Y') }}</td>
                                
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
    <!-- Products Card End -->

</div>
<!-- End::main-content -->

@endsection
