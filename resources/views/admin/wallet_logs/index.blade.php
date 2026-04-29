@extends('layouts.app')
@section('content')

<!-- Start::main-content -->
<div class="main-content px-4 py-6 bg-gray-100 dark:bg-gray-900 min-h-screen">

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
        <div class="flex items-center space-x-2">
            <!-- Home Icon -->
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
                        <span class="text-gray-700 dark:text-gray-200">Wallet Logs</span>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- Alerts -->
    <div class="col-span-12 mb-4">
        @if (Session::has('success'))
            <div class="bg-green-100 text-green-700 p-3 rounded text-xs dark:bg-green-900/30 dark:text-green-300">
                {{ Session::get('success') }}
            </div>
        @endif

        @if (Session::has('failure'))
            <div class="bg-red-100 text-red-700 p-3 rounded text-xs dark:bg-red-900/30 dark:text-red-300">
                {{ Session::get('failure') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-3 rounded text-xs dark:bg-red-900/30 dark:text-red-300">
                <ul class="list-disc ml-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Wallet Logs Card -->
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">

                <!-- Card Header -->
                <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="text-gray-700 dark:text-gray-200 font-medium text-lg flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 3h12v2H4V3zm0 4h12v2H4V7zm0 4h12v2H4v-2zm0 4h12v2H4v-2z"/>
                        </svg>
                        Wallet Logs
                    </h5>

                    <!-- Filter & Refresh -->
                    <div class="flex items-center gap-3">
                        <button 
                            onclick="document.getElementById('filterModal').classList.remove('hidden')" 
                            class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-3 py-2 rounded-md text-xs font-medium transition">
                            🔍 Filter
                        </button>

                        <button 
                            id="reload_txns_tbl"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-2 rounded transition">
                            🔄 Refresh
                        </button>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-4 overflow-auto text-xs bg-gray-50 dark:bg-gray-900">
                    <table id="admin_wallet_logs_table" class="w-full border border-gray-200 dark:border-gray-700 border-collapse min-w-[900px]">
                        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-[11px] tracking-wide">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">ID</th>
                                <th class="px-3 py-2 text-left font-semibold">User</th>
                                <th class="px-3 py-2 text-left font-semibold">Transaction ID</th>
                                <th class="px-3 py-2 text-left font-semibold">Action By</th>
                                <th class="px-3 py-2 text-left font-semibold">Txn Category</th>
                                <th class="px-3 py-2 text-left font-semibold">Balance Before</th>
                                <th class="px-3 py-2 text-left font-semibold">Balance After</th>
                                <th class="px-3 py-2 text-left font-semibold">Description</th>
                                <th class="px-3 py-2 text-left font-semibold">Date Added</th>
                                <th class="px-3 py-2 text-left font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-200 text-[12px]">
                            {{-- DataTable or AJAX data here --}}
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
    <!-- Wallet Logs Card End -->

</div>
<!-- End::main-content -->

<!-- Filter Modal -->
<div id="filterModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md p-6 shadow-lg">
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
            <h3 class="text-gray-800 dark:text-gray-200 font-semibold text-lg">Filter Options</h3>
            <button 
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" 
                onclick="document.getElementById('filterModal').classList.add('hidden')">
                ✖
            </button>
        </div>

        <div class="space-y-4 text-gray-700 dark:text-gray-200">
            <div>
                <label class="block text-sm font-medium">Date From</label>
                <input type="date" id="date_from_filter" class="w-full mt-1 px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 focus:ring-blue-500 focus:border-blue-500 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium">Date To</label>
                <input type="date" id="date_to_filter" class="w-full mt-1 px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 focus:ring-blue-500 focus:border-blue-500 text-sm" />
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button 
                onclick="document.getElementById('filterModal').classList.add('hidden')"
                class="bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-md text-xs font-medium transition">
                Cancel
            </button>
            <button 
                id="filter_user_txn_table"
                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-md text-xs font-semibold transition">
                Apply Filter
            </button>
        </div>
    </div>
</div>

@endsection
