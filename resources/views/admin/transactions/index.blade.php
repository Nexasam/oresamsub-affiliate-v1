@extends('layouts.app')
@section('content')

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
                        <span class="text-gray-700 dark:text-gray-200">Transactions</span>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header / Breadcrumb End -->

    <!-- Transactions Card -->
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">

                <!-- Card Header -->
                <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="text-gray-700 dark:text-gray-200 font-medium text-lg flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4h14v2H3V4zm0 4h14v2H3V8zm0 4h14v2H3v-2zm0 4h14v2H3v-2z"/>
                        </svg>
                        All Transactions
                    </h5>

                    <!-- Filter Button -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                            class="rounded-sm px-3 py-1 border border-gray-200 text-gray-500 hover:text-gray-700 hover:bg-gray-100 
                                   dark:border-gray-700 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700 transition flex items-center gap-1">
                            Filter <i class="ti ti-chevron-down"></i>
                        </button>

                        <!-- Dropdown -->
                        <div x-show="open" @click.outside="open = false"
                            x-transition
                            class="absolute right-0 mt-2 bg-white dark:bg-gray-900 shadow-lg rounded-md py-2 w-48 z-50 border border-gray-200 dark:border-gray-700">
                            <a href="javascript:void(0)" class="block px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Basic Filter
                            </a>
                            <a id="reload_user_tbl" href="javascript:void(0)" class="block px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Refresh
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-4 overflow-auto text-xs bg-gray-50 dark:bg-gray-900">
                    @if (Session::has('success'))
                        <div class="bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 p-3 mb-4 rounded">
                            {{ Session::get('success') }}
                        </div>
                    @endif
                    @if (Session::has('failure'))
                        <div class="bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 p-3 mb-4 rounded">
                            {{ Session::get('failure') }}
                        </div>
                    @endif

                    <table id="admin_transactions_table" 
                           class="w-full min-w-[900px] border-collapse border border-gray-200 dark:border-gray-700">
                        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-3 py-2 text-left">ID</th>
                                <th class="px-3 py-2 text-left">User</th>
                                <th class="px-3 py-2 text-left">Wallet</th>
                                <th class="px-3 py-2 text-left">Product Details</th>
                                <th class="px-3 py-2 text-left">Txn Category</th>
                                <th class="px-3 py-2 text-left">Phone</th>
                                <th class="px-3 py-2 text-left">Amount</th>
                                <th class="px-3 py-2 text-left">Discounted Amount</th>
                                <th class="px-3 py-2 text-left">Balance Before</th>
                                <th class="px-3 py-2 text-left">Balance After</th>
                                <th class="px-3 py-2 text-left">Status</th>
                                <th class="px-3 py-2 text-left">Date Added</th>
                                <th class="px-3 py-2 text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200">
                            {{-- Example row --}}
                            <tr>
                                {{-- <td class="px-3 py-2">1</td>
                                <td class="px-3 py-2">John Doe</td>
                                <td class="px-3 py-2">Main</td>
                                <td class="px-3 py-2">1GB MTN Data</td>
                                <td class="px-3 py-2">Data</td>
                                <td class="px-3 py-2">08123456789</td>
                                <td class="px-3 py-2">₦620</td>
                                <td class="px-3 py-2">₦600</td>
                                <td class="px-3 py-2">₦4,000</td>
                                <td class="px-3 py-2">₦3,380</td>
                                <td class="px-3 py-2 text-green-500">Success</td>
                                <td class="px-3 py-2">2025-10-03</td>
                                <td class="px-3 py-2">View</td> --}}
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
    <!-- Transactions Card End -->

</div>
<!-- End::main-content -->

@endsection
