@extends('layouts.app')

@section('content')

<div class="main-content px-4 py-6 bg-gray-100 dark:bg-gray-900 min-h-screen">

    <!-- Breadcrumb -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            <a href="{{ route('dashboard') }}" class="hover:text-blue-500">Dashboard</a>
            / <span class="text-gray-700 dark:text-gray-200">Affiliates</span>
        </div>
    </div>

    <!-- Card -->
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">

            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">

                <!-- Header -->
                <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="text-lg font-medium text-gray-700 dark:text-gray-200">
                        Affiliates
                    </h5>
                </div>

                <!-- Body -->
                <div class="p-4 overflow-auto text-xs bg-gray-50 dark:bg-gray-900">

                    @if (Session::has('success'))
                        <div class="bg-green-100 text-green-700 p-3 mb-4 rounded">
                            {{ Session::get('success') }}
                        </div>
                    @endif

                    @if (Session::has('failure'))
                        <div class="bg-red-100 text-red-700 p-3 mb-4 rounded">
                            {{ Session::get('failure') }}
                        </div>
                    @endif

                    <table class="w-full border border-gray-200 dark:border-gray-700 border-collapse">

                        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-3 py-2">ID</th>
                                <th class="px-3 py-2">Name</th>
                                <th class="px-3 py-2">Email</th>
                                <th class="px-3 py-2">Phone</th>
                                <th class="px-3 py-2">Domain</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Created</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200">

                            @foreach ($affiliates as $index => $affiliate)

                                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">

                                    <td class="px-3 py-2">{{ $index + 1 }}</td>

                                    <td class="px-3 py-2 font-medium">
                                        {{ $affiliate->name }}
                                    </td>

                                    <td class="px-3 py-2">
                                        {{ $affiliate->contact_email }}
                                    </td>

                                    <td class="px-3 py-2">
                                        {{ $affiliate->contact_phone }}
                                    </td>

                                    <td class="px-3 py-2">
                                        {{ $affiliate->domain_url ?? '-' }}
                                    </td>

                                    <td class="px-3 py-2">
                                        @if ($affiliate->activation_status == 1)
                                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">
                                                ACTIVE
                                            </span>
                                        @else
                                            <span class="bg-gray-200 text-gray-700 px-2 py-1 rounded text-xs">
                                                INACTIVE
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2">
                                        {{ $affiliate->created_at->format('d M, Y') }}
                                    </td>

                                    <!-- ACTIONS -->
                                    <td class="px-3 py-2 text-right space-x-2">

                                        <!-- EDIT -->
                                        <a href="{{ route('affiliates.edit', $affiliate->id) }}"
                                           class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs">
                                            Edit
                                        </a>

                                        <!-- OPTIONAL DELETE -->
                                        <form action="{{ route('affiliates.destroy', $affiliate->id) }}"
                                              method="POST"
                                              class="inline-block"
                                              onsubmit="return confirm('Delete this affiliate?')">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs">
                                                Delete
                                            </button>

                                        </form>

                                    </td>

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