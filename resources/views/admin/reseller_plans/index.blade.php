@extends('layouts.app')
@section('content')

<div class="main-content px-4 py-6 bg-gray-100 dark:bg-gray-900 min-h-screen">
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
                        <span class="text-gray-700 dark:text-gray-200">User Plans</span>
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
        <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
            <h5 class="text-gray-700 dark:text-gray-200 font-medium text-lg">Your Affiliate Plans</h5>
        </div>

        <div class="p-4 overflow-auto text-xs bg-gray-50 dark:bg-gray-900">
            <table class="w-full border border-gray-200 dark:border-gray-700 border-collapse">
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
                        <td class="px-3 py-2">{{ $loop->iteration }}</td>
                        <td class="px-3 py-2">{{ $user_plan->user_plan_name }}</td>
                        <td class="px-3 py-2 flex space-x-2">
                            <input type="text"
                                class="w-full p-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                                id="input_{{ $user_plan->id }}"
                                value="{{ $user_plan->updated_user_plan_name ?? '' }}"
                                placeholder="Enter new plan name">

                            <button class="ti-btn ti-btn-primary px-3 py-2 text-xs"
                                onclick="updatePlan({{ $user_plan->id }})">Save</button>
                        </td>
                        <td class="px-3 py-2">{{ $user_plan->created_at->format('Y-m-d') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function updatePlan(id) {
        const name = document.getElementById(`input_${id}`).value;
        const token = '{{ csrf_token() }}';
    
        fetch(`/admin/reseller_plans/${id}/update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({ updated_user_plan_name: name }),
        })
        .then(res => res.json())
        .then(data => {
            alert('✅ ' + data.message);
            setTimeout(() => {
                window.location.reload();
            }, 500); // 5 seconds
        })
        .catch(() => alert('❌ Error saving plan. Please try again.'));
    }
    
</script>

@endsection
