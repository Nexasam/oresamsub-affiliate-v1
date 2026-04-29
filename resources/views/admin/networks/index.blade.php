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
                        <span class="text-gray-700 dark:text-gray-200">Networks</span>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header / Breadcrumb End -->

    <!-- Networks Card -->
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">

                <!-- Card Header -->
                <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="text-gray-700 dark:text-gray-200 font-medium text-lg flex items-center gap-2">
                        <!-- Network SVG -->
                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 3h12v2H4V3zm0 4h12v2H4V7zm0 4h12v2H4v-2zm0 4h12v2H4v-2z"/>
                        </svg>
                        Main Networks
                    </h5>
                </div>

                <!-- Card Body -->
                <div class="p-4 overflow-auto text-xs bg-gray-50 dark:bg-gray-900">
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-4 lg:grid-cols-4 gap-4">
                        <!-- MTN -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg p-4 flex flex-col items-center justify-center gap-2 transition duration-200">
                          <img width="60" height="60" src="{{ asset('assets/template2/images/mtn.png') }}" alt="MTN">
                          <span class="text-gray-700 dark:text-gray-200 font-medium">MTN</span>
                        </div>
                
                        <!-- GLO -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg p-4 flex flex-col items-center justify-center gap-2 transition duration-200">
                          <img width="60" height="60" src="{{ asset('assets/template2/images/glo.png') }}" alt="GLO">
                          <span class="text-gray-700 dark:text-gray-200 font-medium">GLO</span>
                        </div>
                
                        <!-- Airtel -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg p-4 flex flex-col items-center justify-center gap-2 transition duration-200">
                          <img width="60" height="60" src="{{ asset('assets/template2/images/airtel.png') }}" alt="Airtel">
                          <span class="text-gray-700 dark:text-gray-200 font-medium">Airtel</span>
                        </div>
                
                        <!-- 9Mobile -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg p-4 flex flex-col items-center justify-center gap-2 transition duration-200">
                          <img width="60" height="60" src="{{ asset('assets/template2/images/ninemobile.png') }}" alt="9Mobile">
                          <span class="text-gray-700 dark:text-gray-200 font-medium">9Mobile</span>
                        </div>
                
                    </div>
                </div>
                

            </div>
        </div>
    </div>
    <!-- Networks Card End -->

</div>
<!-- End::main-content -->

@endsection
