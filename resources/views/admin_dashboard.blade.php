@extends('layouts.app')

@section('content')
<div class="main-content ">

    @php
        $sidebar_color =  App\Models\AdminColorSetting::where('color_name','site_admin_sidebar_color')->first(); 
        $sidebar_color = $sidebar_color->color_value ?? '#6B21A8';
        //   echo $sidebar_color;
    @endphp

    <!-- Page Header -->
    <div class="block justify-between page-header md:flex ">
        <div>
            {{-- <p>Current locale: {{ app()->getLocale() }}</p> --}}
            <h3 class="text-gray-700 hover:text-gray-900 dark:text-gray-200 dark:hover:text-white text-2xl font-medium"> <small style=" font-size: 14px;">{{ __('messages.Welcome') }} <strong>{{ $user->first_name. ' '. $user->last_name }}</strong></small> </h3>
            @if(session('affiliate') && session('affiliate')->parent_website)
                  <div class="mb-3">
                      <a
                          href="{{ session('affiliate')->parent_website.'/login' }}"
                          target="_blank"
                          class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-800 underline"
                      >
                          🌐 Visit Parent Website
                      </a>
                  </div>
              @endif

        </div>
        
    </div>
    <!-- Page Header Close -->

    


                    <div class="grid grid-cols-12">
                
                        
                            {{-- MODERN DASHBOARD CARDS --}}
                                <div class="col-span-12 grid gap-5 p-3 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">

                                    @php
                                        $cards = [
                                            [
                                                'title'   => 'Total Users',
                                                'value'   => number_format(count($users ?? [])),
                                                'desc'    => 'Registered platform users',
                                                'icon'    => '<path d="M17 20h5V4H2v16h5m10 0v-6H7v6h10z"/>',
                                                'bgClass' => 'bg-blue-50 dark:bg-blue-900/10',
                                                'textClass' => 'text-blue-600 dark:text-blue-400',
                                            ],
                                            [
                                                'title'   => 'Transactions',
                                                'value'   => number_format(count($transactions) ?? 0),
                                                'desc'    => 'Total transactions processed',
                                                'icon'    => '<path d="M3 10h11l-1-2H3V6h7l-1-2H3V2h13l4 8-4 8H3v-2h11l1-2H3v-2z"/>',
                                                'bgClass' => 'bg-green-50 dark:bg-green-900/10',
                                                'textClass' => 'text-green-600 dark:text-green-400',
                                            ],
                                            [
                                                'title'   => 'Product Plans',
                                                'value'   => number_format(count($product_plans) ?? 0),
                                                'desc'    => 'Available active plans',
                                                'icon'    => '<path d="M3 7h18M3 12h18M3 17h18"/>',
                                                'bgClass' => 'bg-yellow-50 dark:bg-yellow-900/10',
                                                'textClass' => 'text-yellow-600 dark:text-yellow-400',
                                            ],
                                            [
                                                'title'   => 'Wallet Balance',
                                                'value'   => '₦' . number_format(auth()->user()->main_wallet ?? 0, 2),
                                                'desc'    => 'Your wallet balance.',
                                                'icon'    => '<path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2M7 10h8"/>',
                                                'bgClass' => 'bg-purple-50 dark:bg-purple-900/10',
                                                'textClass' => 'text-purple-600 dark:text-purple-400',
                                            ],
                                            [
                                                'title'   => 'Total Balances',
                                                'value'   => '₦' . number_format($main_wallet_balances ?? 0, 2),
                                                'desc'    => 'Cumulative balances',
                                                'icon'    => '<path d="M12 8v4l3 3"/>',
                                                'bgClass' => 'bg-red-50 dark:bg-red-900/10',
                                                'textClass' => 'text-red-600 dark:text-red-400',
                                            ],
                                        ];
                                    @endphp

                                    {{-- Stat cards --}}
                                    @foreach ($cards as $card)
                                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm hover:shadow-md transition transform hover:-translate-y-0.5 p-4 flex flex-col justify-between">
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

                                {{-- FUND WALLET / VIRTUAL ACCOUNTS - FULL ROW --}}
                                {{-- FUND WALLET / VIRTUAL ACCOUNTS - FULL ROW (DUMMY VALUES) --}}
                                <div class="col-span-12 sm:col-span-2 md:col-span-3 lg:col-span-12 p-2">
                                  <div class="w-full p-3 rounded-xl shadow-md bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                
                                      @foreach ($user_virtual_accounts as $user_virtual_account)
                                      <div
                                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-0.5 p-3 flex flex-col justify-between text-[13px]">
                                        <div class="flex items-start gap-2">
                                          <div class="p-1.5 rounded-md bg-blue-100 dark:bg-blue-900/30">
                                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                              stroke-width="1.5">
                                              <path
                                                d="M3 10h18M3 14h18M3 6h18c.553 0 1 .447 1 1v12c0 .553-.447 1-1 1H3c-.553 0-1-.447-1-1V7c0-.553.447-1 1-1z" />
                                            </svg>
                                          </div>
                                          <div class="flex-1 min-w-0">
                                            <h4 class="text-[12px] font-semibold text-gray-600 dark:text-gray-300 truncate">
                                              {{ $user_virtual_account->bank_name }}
                                            </h4>
                                            <div class="mt-0.5">
                                              <div class="text-base font-bold text-gray-900 dark:text-white">
                                                {{ $user_virtual_account->account_number }}
                                              </div>
                                              <p class="text-[12px] text-gray-500 dark:text-gray-400 mt-0.5">
                                                {{ $user_virtual_account->account_name }}
                                              </p>
                                              <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                                Charges:
                                                {{ $user_virtual_account->funding_option->bank_codes->first()->bank_charges }}
                                                {{ $user_virtual_account->funding_option->bank_codes->first()->rate_category == 'percent' ? '%' : 'Naira' }}
                                                |
                                                {{ $user_virtual_account->funding_option->bank_codes->first()->short_description }}
                                              </p>
                                            </div>
                                          </div>
                                        </div>
                                      </div>
                                      @endforeach
                                
                                    </div>
                                  </div>
                                </div>
                                



                    </div>
    </div>
      
        <div class="col-span-12 xxl:col-span-12 px-6 rounded-lg">

            {{-- <div class="box w-full mb-4">
                <div class="box-header">
                    <div class="sm:flex">
                        <h5 class="box-title my-auto">Recent Transactions</h5>
                    </div>
                </div>
                <livewire:transactions-table />

            </div> --}}

        

            <div class="box dark:bg-gray-900 dark:text-gray-100">
                <div class="box-header dark:bg-gray-800">
                  <div class="sm:flex">
                    <h5 class="box-title my-auto dark:text-gray-100">Recent Transactions</h5>
                    <div class="box-header dark:bg-gray-800">
                      <div class="flex items-center">
                        @if (env('APP_NAME') == 'OresamSub')
                          <!-- Refresh button -->
                          <button
                            type="button"
                            id="reload_txns_tbl"
                            class="w-full text-white bg-emerald-600 hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-400 
                              font-medium rounded-lg text-sm px-4 py-2 text-left"
                          >
                            Refresh
                          </button>
                        @endif
              
                        <div
                          class="hs-dropdown ti-dropdown block ms-auto my-auto sm:flex items-center justify-between dark:bg-gray-800"
                        >
                          <div id="hs-slide-down-animation-modal" class="hs-overlay hidden ti-modal">
                            <div
                              class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out dark:bg-gray-900 dark:text-gray-100"
                            >
                              <div class="ti-modal-content dark:bg-gray-900">
                                <div class="ti-modal-header dark:bg-gray-800 dark:text-gray-100">
                                  <h3 class="ti-modal-title">Filter Options</h3>
                                  <button
                                    type="button"
                                    class="hs-dropdown-toggle ti-modal-close-btn"
                                    data-hs-overlay="#hs-slide-down-animation-modal"
                                  >
                                    <span class="sr-only">Close</span>
                                    <svg
                                      class="w-3.5 h-3.5 text-gray-700 dark:text-gray-200"
                                      width="8"
                                      height="8"
                                      viewBox="0 0 8 8"
                                      fill="none"
                                      xmlns="http://www.w3.org/2000/svg"
                                    >
                                      <path
                                        d="M0.258206 1.00652C0.351976 0.912791 0.479126 0.860131 0.611706 0.860131C0.744296 0.860131 0.871447 0.912791 0.965207 1.00652L3.61171 3.65302L6.25822 1.00652C6.30432 0.958771 6.35952 0.920671 6.42052 0.894471C6.48152 0.868271 6.54712 0.854471 6.61352 0.853901C6.67992 0.853321 6.74572 0.865971 6.80722 0.891111C6.86862 0.916251 6.92442 0.953381 6.97142 1.00032C7.01832 1.04727 7.05552 1.1031 7.08062 1.16454C7.10572 1.22599 7.11842 1.29183 7.11782 1.35822C7.11722 1.42461 7.10342 1.49022 7.07722 1.55122C7.05102 1.61222 7.01292 1.6674 6.96522 1.71352L4.31871 4.36002L6.96522 7.00648C7.05632 7.10078 7.10672 7.22708 7.10552 7.35818C7.10442 7.48928 7.05182 7.61468 6.95912 7.70738C6.86642 7.80018 6.74102 7.85268 6.60992 7.85388C6.47882 7.85498 6.35252 7.80458 6.25822 7.71348L3.61171 5.06702L0.965207 7.71348C0.870907 7.80458 0.744606 7.85498 0.613506 7.85388C0.482406 7.85268 0.357007 7.80018 0.264297 7.70738C0.171597 7.61468 0.119017 7.48928 0.117877 7.35818C0.116737 7.22708 0.167126 7.10078 0.258206 7.00648L2.90471 4.36002L0.258206 1.71352C0.164476 1.61976 0.111816 1.4926 0.111816 1.36002C0.111816 1.22744 0.164476 1.10028 0.258206 1.00652Z"
                                        fill="currentColor"
                                      />
                                    </svg>
                                  </button>
                                </div>
              
                                <div class="ti-modal-body dark:bg-gray-900 dark:text-gray-100">
                                  <p class="mt-1 text-gray-800 dark:text-gray-200">Phone recharged:</p>
                                  <input
                                    type="text"
                                    id="phone_recharged"
                                    name="phone_recharged"
                                    class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded p-2"
                                  />
                                  <hr class="my-3 border-gray-300 dark:border-gray-700" />
              
                                  <p class="mt-1 text-gray-800 dark:text-gray-200">Filter by Plan Category:</p>
                                  <select
                                    name="product_plan_category_filter"
                                    id="product_plan_category_filter"
                                    class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded p-2"
                                  >
                                    <option value="">Select</option>
                                    @foreach ($product_plan_categories as $plan_category)
                                      <option value="{{ $plan_category->id }}">
                                        {{ $plan_category->product_plan_category_name }}
                                      </option>
                                    @endforeach
                                  </select>
              
                                  <hr class="my-3 border-gray-300 dark:border-gray-700" />
                                  <p class="mt-1 text-gray-800 dark:text-gray-200">Date range:</p>
              
                                  <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-center justify-start space-x-5">
                                      <div>
                                        <p>Date from:</p>
                                        <input
                                          type="date"
                                          id="date_from_filter"
                                          class="border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded p-2"
                                        />
                                      </div>
                                      <div>
                                        <p>Date to:</p>
                                        <input
                                          type="date"
                                          id="date_to_filter"
                                          class="border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded p-2"
                                        />
                                      </div>
                                    </div>
                                  </div>
                                </div>
              
                                <div class="ti-modal-footer dark:bg-gray-800">
                                  <a
                                    id="filter_user_txn_table"
                                    class="ti-btn ti-btn-primary"
                                    data-hs-overlay="#hs-slide-down-animation-modal"
                                    href="javascript:void(0);"
                                  >
                                    Save changes
                                  </a>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
              
                    <div
                      class="hs-dropdown ti-dropdown block ms-auto my-auto dark:bg-gray-900"
                    >
                      <button
                        aria-label="button"
                        id="hs-dropdown-custom-icon-trigger3"
                        type="button"
                        class="hs-dropdown-toggle ti-dropdown-toggle rounded-sm p-2 bg-gray-100 hover:bg-gray-200 border border-gray-200 
                        text-gray-600 focus:ring-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:text-gray-100"
                      >
                        <i class="text-sm leading-none ti ti-dots-vertical"></i>
                      </button>
                      <div
                        class="hs-dropdown-menu ti-dropdown-menu dark:bg-gray-800 dark:border-gray-700"
                        aria-labelledby="hs-dropdown-custom-icon-trigger3"
                      >
                        <a
                          href="javascript:void(0)"
                          class="ti-dropdown-item hs-dropdown-toggle dark:text-gray-100 hover:dark:bg-gray-700"
                          data-hs-overlay="#hs-slide-down-animation-modal"
                        >
                          Basic filter
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              
                <div class="box-body p-0 dark:bg-gray-900">
                  <div id="taskactive" class="" role="tabpanel" aria-labelledby="active-item">
                    <div class="overflow-auto dark:bg-gray-900">
                      <table
                        id="admin_transactions_table"
                        class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-300"
                      >
                        <thead class="bg-gray-50 dark:bg-gray-800">
                          <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Wallet</th>
                            <th>Product Details</th>
                            <th>Txn Category</th>
                            <th>Phone</th>
                            <th>Amount</th>
                            <th>Discounted Amount</th>
                            <th>Balance Before</th>
                            <th>Balance After</th>
                            <th>Status</th>
                            <th>Date Added</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody class="dark:bg-gray-900"></tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              


        </div>

        <div class="col-span-12 xxl:col-span-12 px-6 rounded-lg">

            <div class="grid grid-cols-12 gap-1">
         
                <div class="col-span-12">
                
                    <div class="box bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 rounded-xl shadow-md border border-gray-200 dark:border-gray-800 transition-colors duration-300">
                        <!-- Header -->
                        <div class="box-header border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                          <h5 class="box-title text-lg font-semibold text-gray-900 dark:text-gray-100">Wallet Creditings</h5>
                        </div>
                      
                        <div class="box-body px-4 py-5">
                          <!-- Filter Header -->
                          <div class="flex items-center mb-4">
                            <h5 class="box-title my-auto text-gray-800 dark:text-gray-100">{{ __('messages.Filter Options') }}</h5>
                      
                            <!-- Filter Dropdown -->
                            <div class="hs-dropdown ti-dropdown block ms-auto my-auto sm:flex items-center justify-between">
                              <button
                                type="button"
                                class="hs-dropdown-toggle ti-dropdown-toggle rounded-md px-3 py-1.5 mr-4 border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200 focus:ring-2 focus:ring-gray-300
                                       dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-600 transition-all duration-150">
                                Filter
                                <i class="ti ti-chevron-down"></i>
                              </button>
                      
                              <div class="hs-dropdown-menu ti-dropdown-menu bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-lg rounded-lg p-2">
                                <a href="javascript:void(0)" class="ti-dropdown-item block px-3 py-2 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hs-dropdown-toggle"
                                   data-hs-overlay="#hs-slide-down-animation-modal">Basic filter</a>
                                <a id="reload_txns_tbl" class="ti-dropdown-item block px-3 py-2 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                   href="javascript:void(0)">Refresh</a>
                              </div>
                      
                              <!-- Modal -->
                              <div id="hs-slide-down-animation-modal" class="hs-overlay hidden ti-modal">
                                <div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out">
                                  <div class="ti-modal-content bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-lg shadow-xl">
                                    <!-- Modal Header -->
                                    <div class="ti-modal-header flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                                      <h3 class="ti-modal-title text-lg font-semibold">Filter Options</h3>
                                      <button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#hs-slide-down-animation-modal">
                                        <span class="sr-only">Close</span>
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-300" fill="none" viewBox="0 0 8 8">
                                          <path d="M0.26 1.01a.5.5 0 0 1 .71 0L3.61 3.65 6.26 1a.5.5 0 1 1 .71.71L4.32 4.36l2.65 2.65a.5.5 0 0 1-.71.71L3.61 5.07.96 7.71a.5.5 0 1 1-.71-.71l2.65-2.65L.26 1.71a.5.5 0 0 1 0-.71Z" fill="currentColor"/>
                                        </svg>
                                      </button>
                                    </div>
                      
                                    <!-- Modal Body -->
                                    <div class="ti-modal-body px-4 py-4 space-y-4">
                                      <div>
                                        <label for="txn_reference" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Txn Reference:</label>
                                        <input type="text" id="txn_reference" name="txn_reference"
                                               class="w-full border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2">
                                      </div>
                      
                                      <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date range:</p>
                                        <div class="flex items-center space-x-6">
                                          <div>
                                            <label for="date_from_filter" class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Date from:</label>
                                            <input type="date" id="date_from_filter"
                                                   class="border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2">
                                          </div>
                                          <div>
                                            <label for="date_to_filter" class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Date to:</label>
                                            <input type="date" id="date_to_filter"
                                                   class="border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2">
                                          </div>
                                        </div>
                                      </div>
                                    </div>
                      
                                    <!-- Modal Footer -->
                                    <div class="ti-modal-footer border-t border-gray-200 dark:border-gray-700 px-4 py-3 flex justify-end">
                                      <a id="filter_crystalpay_txn_table"
                                         class="ti-btn ti-btn-primary inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md px-4 py-2 transition-colors duration-150"
                                         data-hs-overlay="#hs-slide-down-animation-modal"
                                         href="javascript:void(0);">
                                        Save changes
                                      </a>
                                    </div>
                                  </div>
                                </div>
                              </div>
                              <!-- End Modal -->
                            </div>
                          </div>
                      
                          <!-- Table -->
                          <div class="overflow-auto mt-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                            <table id="crystal_pay_funding_logs_table" class="w-full text-sm text-left border-collapse">
                              <thead class="bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                                <tr>
                                  <th class="px-3 py-2">ID</th>
                                  <th class="px-3 py-2">User</th>
                                  <th class="px-3 py-2">Txn Reference</th>
                                  <th class="px-3 py-2">Txn Status</th>
                                  <th class="px-3 py-2">Funding Status</th>
                                  <th class="px-3 py-2">Txn Message</th>
                                  <th class="px-3 py-2">Bank</th>
                                  <th class="px-3 py-2">Account Name</th>
                                  <th class="px-3 py-2">Account No</th>
                                  <th class="px-3 py-2">Account Reference</th>
                                  <th class="px-3 py-2">Amount Paid</th>
                                  <th class="px-3 py-2">Amount Charged</th>
                                  <th class="px-3 py-2">Amount Settled</th>
                                  <th class="px-3 py-2">Date Added</th>
                                  <th class="px-3 py-2">Action</th>
                                </tr>
                              </thead>
                              <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300">
                                <!-- Dynamic rows -->
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                      
                      


                    {{-- <div class="box-body">
                      <div class="overflow-auto table-bordered p-4">
                        <table id="basic-table" class="ti-custom-table ti-striped-table ti-custom-table-hover">
                          <thead>
                              <tr>
                             
                                  <td>First Name</td>
                                  <td>Last Name</td>
                                  <td>Action</td>
                              </tr>
                          </thead>
                          <tbody>
                          </tbody>
                        </table>
                      </div>
                     
                    </div> --}}
                     
                      
                  </div>
                </div>
        </div>

    </div>
     
       
    </div>
    <!-- End::row-1 -->



</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>


setInterval(function () {
    location.reload();
}, 1800000); // 30 minutes (30 * 60 * 1000)


function walletBalance() {
    return {
        balance: '0.00',
        loading: false,
        init() {
            this.refreshMainBalances();
            // auto refreshMainBalances every 20s
            setInterval(() => this.refreshMainBalances(), 500000);
        },
        refreshMainBalances() {
            this.loading = true;
            fetch("{{ route('admin.wallet.total_balances') }}")
                .then(res => res.json())
                .then(data => {
                    console.log(data)
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