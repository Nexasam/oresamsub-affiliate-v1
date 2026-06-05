@extends('layouts.app')

@section('content')

<div class="main-content px-4 py-6 bg-gray-100 min-h-screen">

    <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">

        <h2 class="text-lg font-semibold mb-4">Edit Affiliate</h2>

        <form action="{{ route('affiliate.update') }}" enctype="multipart/form-data" method="POST">
            @csrf
            @method('PUT')

            <!-- NAME (READONLY) -->
            <div class="mb-3">
                <label>Name</label>
                <input type="text"
                       value="{{ $affiliate->name }}"
                       class="w-full border p-2 rounded bg-gray-100"
                       readonly>
            </div>

            <div class="mb-4">

                <label class="block text-sm font-medium text-gray-700">
                    Affiliate Logo/Favicon
                </label>
            
                @if ($affiliate->logo)
                    <div class="mb-3">
                        <img src="{{ asset($affiliate->logo) }}"
                             alt="Affiliate Logo"
                             class="w-24 h-24 object-cover rounded-lg border border-gray-300 shadow-sm">
                    </div>
                @else
                    <div class="mb-3 text-sm text-gray-500">
                        No logo uploaded
                    </div>
                @endif
            
                <input type="file"
                       name="logo"
                       accept="image/*"
                       class="w-full border p-2 rounded">
            
                @error('logo')
                    <p class="text-red-500 text-xs mt-1">
                        {{ $message }}
                    </p>
                @enderror
            
            </div>

            <!-- EMAIL (READONLY) -->
            <div class="mb-3">
                <label>Email</label>
                <input type="email"

                       value="{{ $affiliate->contact_email }}"
                       class="w-full border p-2 rounded bg-gray-100"
                       readonly>
            </div>

            <!-- PHONE (READONLY) -->
            <div class="mb-3">
                <label>Phone</label>
                <input type="text"
                       value="{{ $affiliate->contact_phone }}"
                       class="w-full border p-2 rounded bg-gray-100"
                       readonly>
            </div>

            <!-- DOMAIN (READONLY) -->
            <div class="mb-3">
                <label>Domain</label>
                <input type="text"
                       value="{{ $affiliate->domain_url }}"
                       class="w-full border p-2 rounded bg-gray-100"
                       readonly>
            </div>

            <!-- STATUS (READONLY) -->
            <div class="mb-3">
                <label>Status</label>
                <input type="text"
                       value="{{ $affiliate->activation_status ? 'Active' : 'Inactive' }}"
                       class="w-full border p-2 rounded bg-gray-100"
                       readonly>
            </div>



            <!-- ✅ ONLY EDITABLE FIELD -->

            <!-- parent EMAIL (READONLY) -->
            {{-- <div class="mb-3">
                <label>Parent Email</label>
                <input type="email"
                        name="parent_email"
                       value="{{ $affiliate->parent_email }}"
                      class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-400"
                       >
            </div> --}}

            <div class="mb-3">
                <label>Parent Email</label>
                <input type="email"
                        name="parent_email"
                       value="{{ $affiliate->parent_email }}"
                      class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-400"
                       >
            </div>

            <div x-data="{ show: false }" class="relative mb-3">
                <label>Parent Api Key</label>
                <input
                    :type="show ? 'text' : 'password'"
                    name="parent_key"
                    value="{{ $affiliate->parent_key }}"
                    class="w-full border p-2 pr-10 rounded focus:ring-2 focus:ring-blue-400"
                    placeholder="Enter parent key"
                >
            
                <!-- Toggle Icon -->
                <button type="button"
                        @click="show = !show"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500">
                    
                    <!-- Eye Open -->
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg"
                         class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5
                                 c4.478 0 8.268 2.943 9.542 7
                                 -1.274 4.057-5.064 7-9.542 7
                                 -4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
            
                    <!-- Eye Closed -->
                    <svg x-show="show" xmlns="http://www.w3.org/2000/svg"
                         class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13.875 18.825A10.05 10.05 0 0112 19
                                 c-4.477 0-8.268-2.943-9.542-7
                                 a9.956 9.956 0 012.223-3.592M6.228 6.228A9.956 9.956 0 0112 5
                                 c4.478 0 8.268 2.943 9.542 7
                                 a9.97 9.97 0 01-4.132 5.411M15 12a3 3 0 01-3 3
                                 m0-6a3 3 0 013 3m6 6L4 4"/>
                    </svg>
            
                </button>
            
            </div>

            <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                Update
            </button>

        </form>

    </div>

</div>

@endsection