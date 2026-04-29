@extends('layouts.app')
@section('content')

<!-- Start::main-content -->
<div class="main-content px-4 py-6 bg-gray-100 dark:bg-gray-900 min-h-screen">

    <!-- Page Header / Breadcrumb -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
        <div class="flex items-center space-x-2">
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
                        <span class="text-gray-700 dark:text-gray-200">Reseller Plans</span>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header / Breadcrumb End -->

    <!-- Reseller Plans Card -->
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">

                <!-- Card Header -->
                <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="text-gray-700 dark:text-gray-200 font-medium text-lg">
                        Reseller Plans
                    </h5>
                </div>

                <!-- Card Body -->
                <div class="p-4 overflow-auto text-xs bg-gray-50 dark:bg-gray-900">

                    <table id="reseller-plans-table"
                           class="w-full border border-gray-200 dark:border-gray-700 border-collapse">
                        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-3 py-2 text-left">SN</th>
                                <th class="px-3 py-2 text-left">Default Plan Name</th>
                                <th class="px-3 py-2 text-left">Customized Plan Name</th>
                                <th class="px-3 py-2 text-left">Date Added</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200">
                            @foreach ($user_plans as $user_plan)
                                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <td class="px-3 py-2">{{ $loop->index + 1 }}</td>
                                    <td class="px-3 py-2">{{ $user_plan->user_plan_name }}</td>
                                    <td class="px-3 py-2 flex space-x-2">
                                        <input type="text"
                                        class="reseller_inputs w-full p-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        
                                        id="prefix_id{{ $user_plan->id }}"
                                        value="{{ $user_plan->updated_user_plan_name ?? '' }}">
                               
                                        <input type="hidden" id="_token" name="_token" value="{{ csrf_token() }}" />

                                        <button class="mt-1 w-1/4 ti-btn ti-btn-primary edit_class"
                                                type="button" id="{{ $user_plan->id }}">
                                            <span class="loading_span" id="loading_span{{ $user_plan->id }}">save</span>
                                            <span class="display_span" id="display_span{{ $user_plan->id }}">edit</span>
                                        </button>
                                    </td>
                                    <td class="px-3 py-2">{{ $user_plan->created_at }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>

            </div>
        </div>
    </div>
    <!-- Reseller Plans Card End -->

</div>
<!-- End::main-content -->

@endsection
