@extends('layouts.app')

@section('content')
<div class="main-content">

    @php
        $sidebar_color = App\Models\AdminColorSetting::where('color_name','site_admin_sidebar_color')->first(); 
        $sidebar_color = $sidebar_color->color_value ?? '#6B21A8';
    @endphp

    <!-- Page Header -->
    <div class="block justify-between page-header md:flex">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white text-2xl font-medium">
                <small class="text-base">
                    {{ __('messages.Welcome') }} 
                    <strong>{{ $user->first_name . ' ' . $user->last_name }}</strong>
                </small>
            </h3>
        </div>
    </div>
    <!-- Page Header Close -->

    <div class="grid grid-cols-12">

        {{-- DASHBOARD CARDS --}}
        <div class="col-span-12 grid gap-5 p-3 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
            @php
                $cards = [
                    [
                        'title'   => 'Total Users',
                        'value'   => number_format(count($users ?? [])),
                        'desc'    => 'Registered platform users',
                        'icon'    => '<path d="M17 20h5V4H2v16h5m10 0v-6H7v6h10z"/>',
                        'bgClass' => 'bg-blue-50 dark:bg-blue-900/20',
                        'textClass' => 'text-blue-600 dark:text-blue-400',
                    ],
                    [
                        'title'   => 'Transactions',
                        'value'   => number_format($transactions_count ?? 0),
                        'desc'    => 'Total transactions processed',
                        'icon'    => '<path d="M3 10h11l-1-2H3V6h7l-1-2H3V2h13l4 8-4 8H3v-2h11l1-2H3v-2z"/>',
                        'bgClass' => 'bg-green-50 dark:bg-green-900/20',
                        'textClass' => 'text-green-600 dark:text-green-400',
                    ],
                    [
                        'title'   => 'Product Plans',
                        'value'   => number_format(55),
                        'desc'    => 'Available active plans',
                        'icon'    => '<path d="M3 7h18M3 12h18M3 17h18"/>',
                        'bgClass' => 'bg-yellow-50 dark:bg-yellow-900/20',
                        'textClass' => 'text-yellow-600 dark:text-yellow-400',
                    ],
                    [
                        'title'   => 'Wallet Balance',
                        'value'   => '₦' . number_format($wallet_balance ?? 0, 2),
                        'desc'    => 'Main system wallet balance',
                        'icon'    => '<path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2M7 10h8"/>',
                        'bgClass' => 'bg-purple-50 dark:bg-purple-900/20',
                        'textClass' => 'text-purple-600 dark:text-purple-400',
                    ],
                    [
                        'title'   => 'Total Earnings',
                        'value'   => '₦' . number_format($earnings ?? 0, 2),
                        'desc'    => 'Cumulative platform earnings',
                        'icon'    => '<path d="M12 8v4l3 3"/>',
                        'bgClass' => 'bg-red-50 dark:bg-red-900/20',
                        'textClass' => 'text-red-600 dark:text-red-400',
                    ],
                ];
            @endphp

            @foreach ($cards as $card)
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm hover:shadow-md transition hover:-translate-y-0.5 p-4 flex flex-col justify-between">
                    <div class="flex items-start space-x-3">
                        <div class="p-2 rounded-lg {{ $card['bgClass'] }}">
                            <svg class="w-5 h-5 {{ $card['textClass'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                {!! $card['icon'] !!}
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-300 truncate">{{ $card['title'] }}</h4>
                            <div class="mt-2">
                                <div class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 dark:text-white leading-tight">
                                    {{ $card['value'] }}
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $card['desc'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- VIRTUAL ACCOUNTS (Dummy values) --}}
        <div class="col-span-12 p-3">
            <div class="w-full p-4 rounded-2xl shadow-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    {{-- GTBank --}}
                    <x-bank-card bank="GTBank" number="0123456789" name="Adedapo Dolapo" charges="₦20 Flat | Instant Funding" bg="blue" />

                    {{-- FirstBank --}}
                    <x-bank-card bank="FirstBank" number="1234567890" name="Samuel Adebunmi" charges="1% | 24/7 Processing" bg="yellow" />

                    {{-- Access Bank --}}
                    <x-bank-card bank="Access Bank" number="0987654321" name="Dolapo Adedapo" charges="₦30 Flat | Same Day" bg="green" />

                    {{-- UBA --}}
                    <x-bank-card bank="UBA" number="5566778899" name="John Doe" charges="0% | Free Funding" bg="red" />

                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
setInterval(() => location.reload(), 1800000); // Auto refresh every 30 mins

function walletBalance() {
    return {
        balance: '0.00',
        loading: false,
        init() {
            this.refreshMainBalances();
            setInterval(() => this.refreshMainBalances(), 500000);
        },
        refreshMainBalances() {
            this.loading = true;
            fetch("{{ route('admin.wallet.total_balances') }}")
                .then(res => res.json())
                .then(data => {
                    this.balance = Number(data.balance)
                        .toLocaleString('en-NG', { minimumFractionDigits: 2 });
                })
                .catch(() => {
                    this.balance = 'Error';
                })
                .finally(() => {
                    this.loading = false;
                });
        }
    }
}
</script>
@endpush
