@extends('layouts.app')
@section('content')

      <!-- Start::main-content -->
      <div class="main-content">

        <!-- Page Header -->
        <div class="block justify-between page-header md:flex">
            {{-- <div>
                <h3 class="text-gray-700 hover:text-gray-900 dark:text-gray-900 dark:hover:text-white text-2xl font-medium"> Users</h3>
            </div>
            <ol class="flex items-center whitespace-nowrap min-w-0">
              
                <li class="text-sm text-gray-500 hover:text-primary dark:text-white/70 " aria-current="page">
                    Products
                </li>
            </ol> --}}
        </div>
        <!-- Page Header Close -->

        <!-- Start::row-1 -->
        <div class="grid grid-cols-12 gap-1">

          <div class="col-span-12">
            @if (Session::has('success'))
            <div class="bg-success/10 border border-success/10 alert text-success" role="alert">
              Great! {{ Session::get('success') }}
              </div>
            @endif

            @if (Session::has('failure'))
              <div class="bg-danger/10 border border-danger/10 alert text-danger" role="alert">
               Ops! {{ Session::get('failure') }}
              </div>
            @endif
            
            @if ($errors->any())
              <div class="bg-danger/10 border border-danger/10 alert text-danger" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
              </div>
            @endif
          </div>
         
          <div class="col-span-12">
          
              <div class="box">
                <div class="box-header">
                  <h5 class="box-title">Users</h5>
                </div>

                <div class="box-body">
                  <nav class="flex space-x-2" aria-label="Tabs" role="tablist">
                    <button type="button" class="hs-tab-active:bg-primary hs-tab-active:text-white py-3 px-4 inline-flex items-center gap-2 bg-transparent text-sm font-medium text-center text-gray-500 rounded-sm hover:text-primary  dark:text-white/70 dark:hover:text-white active" id="pills-with-brand-color-item-1" data-hs-tab="#pills-with-brand-color-1" aria-controls="pills-with-brand-color-1">
                       View Users
                    </button>
                    <button type="button" class="hs-tab-active:bg-primary hs-tab-active:text-white py-3 px-4 inline-flex items-center gap-2 bg-transparent text-sm font-medium text-center text-gray-500 rounded-sm hover:text-primary  dark:text-white/70 dark:hover:text-white " id="pills-with-brand-color-item-2" data-hs-tab="#pills-with-brand-color-2" aria-controls="pills-with-brand-color-2">
                      Create Users 
                    </button>
                  
                  </nav>

                  <div class="mt-3">
                    <div id="pills-with-brand-color-1" role="tabpanel" aria-labelledby="pills-with-brand-color-item-1">
                      <div class="overflow-auto p-3">
                    
                        <!-- Header + Filter -->
                        <div class="flex items-center justify-between mb-3">
                          <h5 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Filter Options</h5>
                    
                          <!-- Filter Dropdown -->
                          <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open"
                              class="flex items-center gap-1 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 px-2 py-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition text-sm">
                              Filter
                              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                              </svg>
                            </button>
                    
                            <!-- Dropdown Menu -->
                            <div x-show="open" @click.outside="open = false"
                              class="absolute right-0 mt-2 w-36 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-50 text-sm">
                              <a href="javascript:void(0)" @click="$dispatch('open-modal', 'filterModal')" 
                                class="block px-3 py-1.5 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Basic Filter</a>
                              <a id="reload_user_tbl" href="javascript:void(0)"
                                class="block px-3 py-1.5 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Refresh</a>
                            </div>
                          </div>
                        </div>
                    
                        <!-- Users Table -->
                        <div class="overflow-x-auto">
                          <table id="userss_table"
                            class="w-full border border-gray-200 dark:border-gray-700 border-collapse rounded-lg overflow-hidden text-sm">
                            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                              <tr>
                                <th class="px-2 py-1.5 text-left">SN</th>
                                <th class="px-2 py-1.5 text-left">Full Name</th>
                                <th class="px-2 py-1.5 text-left">Main Wallet (₦)</th>
                                <th class="px-2 py-1.5 text-left">Status</th>
                                <th class="px-2 py-1.5 text-left">Email</th>
                                <th class="px-2 py-1.5 text-left">Phone</th>
                                <th class="px-2 py-1.5 text-left">Date Added</th>
                                <th class="px-2 py-1.5 text-left">Last Login</th>
                                <th class="px-2 py-1.5 text-left">Action</th>
                              </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200">
                              {{-- Example Row --}}
                              <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150">
                                <td class="px-2 py-1.5">1</td>
                                <td class="px-2 py-1.5 font-medium">John Doe</td>
                                <td class="px-2 py-1.5">₦12,000</td>
                                <td class="px-2 py-1.5">
                                  <span class="bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 px-1.5 py-0.5 rounded-full text-[11px] font-semibold">
                                    Active
                                  </span>
                                </td>
                                <td class="px-2 py-1.5">john@example.com</td>
                                <td class="px-2 py-1.5">08012345678</td>
                                <td class="px-2 py-1.5">29 Oct, 2025</td>
                                <td class="px-2 py-1.5">10:30 AM</td>
                                <td class="px-2 py-1.5">
                                  <button class="bg-blue-600 hover:bg-blue-500 text-white text-xs px-2 py-1 rounded-md transition">View</button>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    
                      <!-- Filter Modal -->
                      <div x-data="{ show: false }" x-show="show" @open-modal.window="if($event.detail == 'filterModal') show = true" @keydown.escape.window="show = false"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-3" x-cloak>
                        <div class="bg-white dark:bg-gray-900 rounded-md shadow-xl max-w-sm w-full overflow-hidden text-sm">
                          <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-800 dark:text-gray-100 text-sm">Filter Options</h3>
                            <button @click="show = false" class="text-gray-500 hover:text-gray-300 text-xs">✕</button>
                          </div>
                    
                          <div class="px-3 py-3 space-y-3">
                            <div>
                              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Phone</label>
                              <input type="text" id="phone_filter"
                                class="mt-1 w-full rounded-md border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500 text-xs px-2 py-1">
                            </div>
                    
                            <div>
                              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Email or Username</label>
                              <input type="email" id="email_filter"
                                class="mt-1 w-full rounded-md border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500 text-xs px-2 py-1">
                            </div>
                    
                            <div class="flex gap-3">
                              <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date From</label>
                                <input type="date" id="date_from_filter"
                                  class="mt-1 w-full rounded-md border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500 text-xs px-2 py-1">
                              </div>
                              <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date To</label>
                                <input type="date" id="date_to_filter"
                                  class="mt-1 w-full rounded-md border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500 text-xs px-2 py-1">
                              </div>
                            </div>
                          </div>
                    
                          <div class="px-3 py-2 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                            <button @click="show = false"
                              class="px-3 py-1.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-md mr-2 hover:bg-gray-300 dark:hover:bg-gray-600 text-xs transition">
                              Cancel
                            </button>
                            <button id="filter_user_table"
                              class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-md text-xs transition">
                              Apply
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div id="pills-with-brand-color-2" class="hidden"  role="tabpanel" aria-labelledby="pills-with-brand-color-item-2">
                      <div class="overflow-auto">
                        <!-- Start::row-3 -->
                      <div class="grid grid-cols-12 gap-x-6">
                        
                        <div class="col-span-12">
                            <div class="box">
                                
                                <div class="box-body">
                                  <form method="POST" action="{{ route('admin.users.store')}}">
                                    @csrf
                                    <div class="grid lg:grid-cols-2 gap-6 space-y-4 lg:space-y-0">
                                      <div class="space-y-2">
                                            <label class="ti-form-label mb-0">Username</label>
                                            <input type="text" id="username" name="username" required class="my-auto ti-form-input" placeholder="Username">
                                      </div>
                                     
                                       <div class="space-y-2">
                                            <label class="ti-form-label mb-0">First name</label>
                                            <input type="text" id="first_name" name="first_name" class="my-auto ti-form-input" placeholder="Firstname">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="ti-form-label mb-0">Last name</label>
                                            <input type="text" id="last_name" name="last_name" class="my-auto ti-form-input" placeholder="Last name">
                                        </div>
                                        <div class="space-y-2">
                                          <label class="ti-form-label mb-0">PIN</label>
                                          <input type="number" id="pin" name="pin" class="my-auto ti-form-input" placeholder="PIN">
                                        </div> 
                                        <div class="space-y-2">
                                          <label class="ti-form-label mb-0">Other names</label>
                                          <input type="text" id="other_names" name="other_names" class="my-auto ti-form-input" placeholder="Other names">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="ti-form-label mb-0">Phone Number</label>
                                            <input type="number" id="phone_number" name="phone_number" class="my-auto ti-form-input"
                                                placeholder="+91 123-456-789">
                                        </div>

                                        

                                        <div class="space-y-2">
                                          <label class="ti-form-label mb-0">User Plan</label>
                                          <select required id="user_plan_id" name="user_plan_id"  class="my-auto ti-form-select">
                                            <option value="">Select</option>
                                              
                                            @foreach ($user_plans as $user_plan)
                                                <option 
                                                value="{{ $user_plan->id  }}">{{ $user_plan->user_plan_name ?? $user_plan->default_user_plan_name  }}</option>
                                            @endforeach
                              
                                          </select>
                                        
                                          </div>

                                          {{-- <div class="space-y-2">
                                            <label class="ti-form-label mb-0">Role</label>
                                            <select required id="role_id" name="role_id"  class="my-auto ti-form-select">
                                              <option value="">Select</option>
                                                
                                              @foreach ($roles as $role)
                                                  <option 
                                                  value="{{ $role->id  }}">{{  $role->role_name  }}</option>
                                              @endforeach
                                
                                            </select>
                                          
                                            </div>
                                          --}}



                                        <div class="space-y-2">
                                            <label class="ti-form-label mb-0">Email Address</label>
                                            <input type="email" id="email" name="email" class="my-auto ti-form-input"
                                                placeholder="your@site.com">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="ti-form-label mb-0">Password</label>
                                            <input type="password" id="password" name="password" class="ti-form-input" placeholder="password">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="ti-form-label mb-0">Confirm Password</label>
                                            <input type="password" id="password_confirmation" name="password_confirmation" class="ti-form-input" placeholder="confirm password">
                                        </div>
                                    
                                        {{-- <div class="space-y-2 ">
                                            <label class="ti-form-label mb-0">Gender</label>
                                            <ul class="flex flex-col sm:flex-row">
                                                <li
                                                    class="ti-list-group gap-x-2.5 bg-white border text-gray-800 sm:-ms-px sm:mt-0 sm:first:rounded-se-none sm:first:rounded-ss-none sm:first:rounded-es-sm sm:last:rounded-es-none sm:last:rounded-ee-none sm:last:rounded-se-sm dark:bg-bgdark dark:border-white/10 dark:text-gray-900">
                                                    <div class="relative flex items-start w-full">
                                                        <div class="flex items-center h-5">
                                                            <input id="hs-horizontal-list-group-item-radio-1"
                                                                name="gender" type="radio" value="female"
                                                                class="ti-form-radio" checked>
                                                        </div>
                                                        <label for="hs-horizontal-list-group-item-radio-1"
                                                            class="ms-3 block w-full text-sm text-gray-600 dark:text-white/70">
                                                            Female
                                                        </label>
                                                    </div>
                                                </li>
        
                                                <li
                                                class="ti-list-group gap-x-2.5 bg-white border text-gray-800 sm:-ms-px sm:mt-0 sm:first:rounded-se-none sm:first:rounded-ss-none sm:first:rounded-es-sm sm:last:rounded-es-none sm:last:rounded-ee-none sm:last:rounded-se-sm dark:bg-bgdark dark:border-white/10 dark:text-gray-900">
                                                <div class="relative flex items-start w-full">
                                                        <div class="flex items-center h-5">
                                                            <input id="hs-horizontal-list-group-item-radio-2"
                                                                name="gender" type="radio" value="male"
                                                                class="ti-form-radio">
                                                        </div>
                                                        <label for="hs-horizontal-list-group-item-radio-2"
                                                            class="ms-3 block w-full text-sm text-gray-600 dark:text-white/70">
                                                            Male
                                                        </label>
                                                    </div>
                                                </li>
        
                                            
                                            </ul>
                                        </div> --}}
        
                                
                                        {{-- <div class="space-y-2">
                                            <label class="ti-form-label mb-0">Address</label>
                                            <input type="text" id="address" name="address" class="my-auto ti-form-input" placeholder="Address">
                                        </div> --}}
                                        <br>
                                    </div>
                                    <div class="my-5">
                                        <button type="submit" class="ti-btn ti-btn-primary w-full">Submit</button>
                                    </div>
        
                                </form>
                                </div>
                            </div>
                        </div>
                      </div>
                    <!-- End::row-3 -->   
                  </div>  

                    </div>
                    <div id="pills-with-brand-color-3" class="hidden" role="tabpanel" aria-labelledby="pills-with-brand-color-item-3">
                      <p class="text-gray-500 dark:text-white/70 p-5 border rounded-sm dark:border-white/10 border-gray-200">
                        Unbelievable healthy snack success stories. 12 facts about safe food handling tips that will impress your friends. Restaurant weeks by the numbers. Will mexican food ever rule the world? The 10 best thai restaurant youtube videos. How restaurant weeks can make you sick. The complete beginner's guide to cooking healthy food. Unbelievable food stamp success stories. How whole foods markets are making the world a better place. 16 things that won't happen in dish reviews.
                      </p>
                    </div>
                  </div>
                </div>
               
                {{-- <div class="box-body">
                 
                </div> --}}
              </div>
             
               
                
            </div>
          </div>
        </div>
        <!-- End::row-1 -->


        <!-- Start::row-3 -->
        {{-- <div class="grid grid-cols-12 gap-6">
          <div class="col-span-12">
            <div class="box">
              <div class="box-header">
                <h5 class="box-title">Reactivity DataTable</h5>
              </div>
              <div class="box-body space-y-3">
                <div class="reactivity-data">
                  <button type="button" class="ti-btn ti-btn-primary" id="reactivity-add">Add New Row</button>
                  <button type="button" class="ti-btn ti-btn-primary" id="reactivity-delete">Remove Row</button>
                  <button type="button" class="ti-btn ti-btn-primary" id="clear">Empty the table</button>
                  <button type="button" class="ti-btn ti-btn-primary" id="reset">Reset</button>
                </div>
                <div class="overflow-hidden table-bordered">
                  <div id="reactivity-table" class="ti-custom-table ti-striped-table ti-custom-table-hover"></div>
                </div>
              </div>
            </div>
          </div>
        </div> --}}
        <!-- End::row-3 -->

        <!-- Start::row-3 -->
        {{-- <div class="grid grid-cols-12 gap-6">
          <div class="col-span-12">
            <div class="box">
              <div class="box-header">
                <h5 class="box-title">Download DataTable</h5>
              </div>
              <div class="box-body space-y-3">
                <div class="download-data">
                    <button type="button" class="ti-btn ti-btn-primary" id="download-csv">Download CSV</button>
                    <button type="button" class="ti-btn ti-btn-primary" id="download-json">Download JSON</button>
                    <button type="button" class="ti-btn ti-btn-primary" id="download-xlsx">Download XLSX</button>
                    <button type="button" class="ti-btn ti-btn-primary" id="download-pdf">Download PDF</button>
                    <button type="button" class="ti-btn ti-btn-primary" id="download-html">Download HTML</button>
                </div>
                <div class="overflow-hidden table-bordered">
                  <div id="download-table" class="ti-custom-table ti-striped-table ti-custom-table-hover"></div>
                </div>
              </div>
            </div>
          </div>
        </div> --}}
        <!-- End::row-3 -->

      </div>
      <!-- Start::main-content -->

       
@endsection

