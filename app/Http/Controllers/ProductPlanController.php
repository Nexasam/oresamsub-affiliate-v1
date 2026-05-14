<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\Product;
use App\Models\UserPlan;
use App\Models\Affiliate;
use App\Models\Automation;
use App\Models\ProductPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;
use App\Models\AffiliateUserPlan;
use Illuminate\Support\Facades\DB;
use App\Models\ProductPlanCategory;
use App\Models\AffiliateProductPlan;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class ProductPlanController extends Controller
{
    public function index(){
        // dd('na here');
        $product_plans = ProductPlan::with(['product','product_plan_category','automation'])
        ->where('visibility',1)
        ->get();
        $product_plan_categories = ProductPlanCategory::get();
        $data['product_plans'] = $product_plans;
        $data['product_plan_categories'] = $product_plan_categories;
        // dd($data);

        
        return view('admin.product_plans.index')->with($data);
    }

    public function updateAffiliatePlanProfits(Request $request){
     
          $request->validate([
              'plan_id' => 'required|exists:affiliate_product_plans,product_plan_id',
              'profits' => 'required|array',
          ]);

          $planId = $request->input('product_plan_id');
          $profits = $request->input('profits');
      

          $plan = AffiliateProductPlan::with('product_plan')->where('product_plan_id',$request->plan_id)->first();
      
          $level = auth()->user()->user_plan->plan_level;

          $afflev = "aff_level_{$level}_max_profit";

          $defaultval = $plan->product_plan->profit_category == 'flat' ? 50 : 1;          
          $max_profit = $plan->product_plan->$afflev ?? $defaultval;

         

          // Update each user_level_X_profit dynamically
          $index = 1;
          foreach ($request->profits as $label => $value) {
              $field = 'user_level_' . $index . '_profit';
              if($value > $max_profit){
                return response()->json(['status' => false,'message' => 'profit setting cannot be more than the value:'.$max_profit]);
              }
              $plan->$field = $value ?? 0;
              $index++;
          }
      
          $plan->save();
      
          return response()->json(['status' => true,'message' => 'successful','profits' => $profits]);
    
    }


    public function toggle_product_public_visibility(Request $request){    
      $validator = Validator::make($request->all(), [
        'productPlanId' => 'required|max:255|exists:affiliate_product_plans,product_plan_id',
        'token' => 'required',
      ]);
      
      if ($validator->stopOnFirstFailure()->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
      }

      $detail = AffiliateProductPlan::where('product_plan_id',$request->productPlanId)->first();
      $update = $detail->public_visibility ? 0 : 1;
      $detail->update([
        'public_visibility' => $update
      ]);

      return response()->json(['status'=>'1', 'message'=>'success' ]);     
    }

    public function toggle_product_visibility(Request $request){
      
      $validator = Validator::make($request->all(), [
        'productPlanId' => 'required|max:255|exists:product_plans,id',
        'token' => 'required',
      ]);
      

      if ($validator->stopOnFirstFailure()->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
      }

      $detail = AffiliateProductPlan::where('product_plan_id',$request->productPlanId)->first();
      $update = $detail->visibility ? 0 : 1;
      $detail->update([
        'visibility' => $update
      ]);

      return response()->json(['status'=>'1', 'message'=>'success']);
    }




    public function product_plan_details($id){
        $data['automations'] = Automation::select('id','automation_name')->get();
        $data['networks'] = Network::select('id','network_name')->get();
        $data['products'] = Product::select('id','product_name')->get();

        $data['basic_plan'] = UserPlan::where('user_plan_name','Basic Plan')->first();
        $data['gold_plan'] = UserPlan::where('user_plan_name','Gold Reseller Plan')->first();
        $data['diamond_plan'] = UserPlan::where('user_plan_name','Diamond Reseller Plan')->first();
        $data['platinum_plan'] = UserPlan::where('user_plan_name','Platinum Reseller Plan')->first();
        
        $product_plan_categories = ProductPlanCategory::with(['product' => function($query){
          $query->where('slug','data');
        }])->latest()->get();
        
        $data['product_plan_categories'] = $product_plan_categories;
        $product_plan_details = ProductPlan::with('automation')->where('id',$id)->first();
        $data['product_plan'] = $product_plan_details;
        return view('admin.product_plans.product_plan_details')->with($data);
    }
    

   
   public function admin_fetch_product_plans(Request $request){
        // Fetch all product plans with related data
        $data = ProductPlan::with(['product_plan_category.network', 'product_plan_category.product','affiliate_product_plan'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // return $data;
    
        // Get all affiliate-added plan IDs
        $affiliatePlanIds = AffiliateProductPlan::pluck('product_plan_id')->toArray();
    
        return DataTables::of($data)
            ->addIndexColumn()

            ->setRowAttr([
              'data-id' => function($data) {
                  return $data->id; // Add row identifier
              },
          ])
          ->setRowClass(function ($data) use ($affiliatePlanIds) {
              return in_array($data->id, $affiliatePlanIds) ? '' : 'opacity-50';
          })
    
            // SN Column
            ->addColumn('DT_RowIndex', fn($data) => $data->id)
    
            // Product Name
            ->addColumn('product_name', fn($data) => $data->product_plan_category->product->product_name ?? '—')
    
            // Network Name
            ->addColumn('network_name', fn($data) => $data->product_plan_category->network->network_name ?? '—')
    
            // Plan Name
            ->addColumn('product_plan_name', fn($data) => $data->product_plan_name ?? '—')
    
            // Category
            ->addColumn('category', fn($data) => $data->product_plan_category->product_plan_category_name ?? '—')
    
            // Data Size
            ->addColumn('data_size_in_mb', fn($data) => $data->data_size_in_mb ? $data->data_size_in_mb . ' MB' : '—')
    
            // Validity
            ->addColumn('validity_in_days', fn($data) => $data->validity_in_days ? $data->validity_in_days . ' days' : '—')
    
            // Cost Price
            ->addColumn('cost_price', fn($data) => '₦' . number_format((float)$data->cost_price, 2))
    
            // Profit Range
            ->addColumn('max_profit_range', function ($data) {
              
                $level = auth()->user()->user_plan->plan_level;

                $afflev = "aff_level_{$level}_max_profit";

                if($data->profit_category == 'percent'){
                  $res = $data->$afflev ?? 1;
                }else{
                $res = $data->$afflev ?? 50;
                }


                return $res;
            })

            ->addColumn('user_plan_profit', function ($data) use ($affiliatePlanIds) {
              // Build titles from AffiliateUserPlan (levels 1..6)
              $affiliateUserPlans = AffiliateUserPlan::whereIn('plan_level', ['1','2','3','4','5','6'])
                  ->orderBy('plan_level')
                  ->get()
                  ->keyBy('plan_level');
          
              $titles = [];
              for ($i = 1; $i <= 6; $i++) {
                  $plan = $affiliateUserPlans->get((string) $i);
                  $titles[] = $plan ? ($plan->updated_user_plan_name ?? $plan->user_plan_name) : 'L' . $i;
              }
          
              // Determine if category is percent or flat
              $isPercent = $data->profit_category === 'percent';
              $profitLabel = $isPercent ? 'Percentage (%)' : 'Flat Rate';
          
              // Build profits array with formatted values
              $profits = [];
              foreach ($titles as $index => $title) {
                  $value = $data->affiliate_product_plan?->{'user_level_' . ($index + 1) . '_profit'} ?? 1;
                  $profits[$title] = $isPercent && $value !== '' ? $value : $value;
              }
          
              // Encode JSON safely
              $profitsJson = json_encode($profits, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
          
              // Disable editing if plan not added
              $disabledAttr = in_array($data->id, $affiliatePlanIds) ? '' : 'data-disabled="1"';
          
              return <<<HTML
              <div 
                  x-data="profitEditorComponent" 
                  class="relative" 
                  data-profits='{$profitsJson}' 
                  data-plan-id="{$data->id}" 
                  {$disabledAttr}
              >
                  <!-- Toggle Button -->
                  <button 
                      @click="open = !open" 
                      class="px-2 py-1 text-xs rounded-md 
                             bg-gray-100 dark:bg-gray-900 
                             hover:bg-gray-200 dark:hover:bg-gray-700 
                             text-gray-700 dark:text-gray-200 
                             transition-all"
                  >
                      Manage Profits 
                      <span x-text="open ? '▲' : '▼'"></span>
                  </button>
              
                  <!-- Dropdown -->
                  <div 
                      x-show="open" 
                      x-transition
                      class="absolute mt-2 right-0 
                             bg-white/95 dark:bg-gray-900 
                             backdrop-blur-sm 
                             border border-gray-200 dark:border-gray-700 
                             rounded-xl shadow-xl p-3 space-y-2 z-50 w-60"
                      style="display:none;"
                  >
                      <!-- Profit Type Header -->
                      <div class="flex items-center justify-between text-xs font-semibold 
                                  text-gray-700 dark:text-gray-300 mb-2 border-b 
                                  border-gray-200 dark:border-gray-700 pb-1">
                          <span>Profit Type:</span>
                          <span class="italic text-blue-600 dark:text-blue-400">{$profitLabel}</span>
                      </div>
          
                      <!-- Editable fields -->
                      <template x-for="(value, level) in profits" :key="level">
                          <div class="flex items-center justify-between space-x-2">
                              <span class="text-xs font-medium text-gray-700 dark:text-gray-200" x-text="level"></span>
                              <input 
                                  type="text" 
                                  x-model="profits[level]" 
                                  class="w-20 border border-gray-300 dark:border-gray-600 
                                         rounded-md px-1.5 py-1 text-xs 
                                         bg-gray-50 dark:bg-gray-800 
                                         text-gray-800 dark:text-gray-100 
                                         focus:outline-none focus:ring-1 
                                         focus:ring-blue-500"
                                  :disabled="\$root.hasAttribute('data-disabled')"
                              />
                          </div>
                      </template>
              
                      <!-- Save button -->
                      <button 
                          @click="updatePlanProfit" 
                          :disabled="saving"
                          class="mt-3 w-full 
                                 bg-blue-600 hover:bg-blue-700 
                                 disabled:opacity-70 
                                 text-white text-xs py-1.5 
                                 rounded-md transition-all"
                      >
                          <span x-show="!saving && !success">Save</span>
                          <span x-show="saving">Saving...</span>
                          <span x-show="success">Saved ✓</span>
                      </button>
                  </div>
              </div>
              HTML;
             })
          
          

            ->addColumn('admin_visibility', function ($data) {
              return $data->visibility == 1 
                  ? '<span class="text-green-600 dark:text-green-400">Available</span>'
                  : '<span class="text-red-600 dark:text-red-400">Not Available</span>';
             })
          
            ->addColumn('affiliate_visibility',function($data){
              $escapedUrl = htmlspecialchars(json_encode($data->id));
              $token = htmlspecialchars(json_encode(csrf_token()));
              $checked = $data->affiliate_product_plan?->visibility == 1 ? 'checked':'';
              $actual_value = $data->affiliate_product_plan?->visibility;
              $checkedd = htmlspecialchars(json_encode($actual_value));
              $toggle_btn = '<div class="flex items-center">';
              $toggle_btn .=  '<input onchange="toggleProductPlanVisibility('.$escapedUrl.','.$token.','.$checkedd.')" type="checkbox" id="hs-basic-with-description-checked'.$data->id.'" class="ti-switch" '.$checked.'>';
              $toggle_btn .=  '<label for="hs-basic-with-description-checked" class="text-sm text-gray-500 ms-3 dark:text-white/70 "></label>';
              $toggle_btn .=  ' <span class="badge rounded-sm bg-success/10 text-success hidden" id="nnotification'.$data->id.'"></span>  </div>';
              
              return $toggle_btn;
            })
    
            // Affiliate Status (Add Plan Button)
            ->addColumn('affiliate_status', function ($data) use ($affiliatePlanIds) {
                $isAdded = in_array($data->id, $affiliatePlanIds);
                $isAddedJs = $isAdded ? 'true' : 'false';
                $route = route('admin.affiliate.addProductPlan');
                $csrf = csrf_token();
                $productNameEscaped = str_replace(["'", '"'], ["\\'", '\\"'], $data->product_plan_name);
    
                return <<<HTML
                <div 
                    x-data="{
                        added: {$isAddedJs},
                        loading: false,
                        async addPlan(planId, planName) {
                            if (this.added) return;
                            this.loading = true;
                            try {
                                const res = await fetch('{$route}', {
                                    method: 'POST',
                                    headers: { 
                                        'Content-Type': 'application/json', 
                                        'Accept': 'application/json', 
                                        'X-CSRF-TOKEN': '{$csrf}' 
                                    },
                                    body: JSON.stringify({ product_plan_id: planId, product_plan_name: planName })
                                });
                                const data = await res.json();
                                this.loading = false;

                                if (data.status === 1 || data.status === 'exists') {
                                    this.added = true;

                                    // ✅ Instantly remove gray opacity
                                    const row = document.querySelector(`[data-id='\${planId}']`);
                                    if (row) {
                                        row.classList.remove('opacity-50');
                                    }

                                    // ✅ Enable and activate the visibility toggle if present
                                    const toggle = document.querySelector(`#hs-basic-with-description-checked\${planId}`);
                                    if (toggle) {
                                        toggle.removeAttribute('disabled');
                                        toggle.checked = true;
                                    }
                                } else {
                                    alert('Failed to add plan!');
                                }
                            } catch (e) {
                                console.error(e);
                                this.loading = false;
                                alert('Error adding plan!');
                            }
                        }
                    }"
                >
                    <button 
                        :disabled="added || loading"
                        :class="added 
                            ? 'bg-green-600 text-white cursor-default' 
                            : (loading 
                                ? 'bg-blue-400 text-white cursor-wait' 
                                : 'bg-gray-400 hover:bg-blue-600 text-white')"
                        x-text="added ? 'Added ✓' : (loading ? 'Adding...' : 'Add Plan')"
                        class="px-2 py-1 text-xs rounded transition duration-200"
                        @click.prevent="addPlan({$data->id}, '{$productNameEscaped}')"
                    ></button>
                </div>
                HTML;
                  })
                    
            // Created / Updated
            ->addColumn('created_at', fn($data) => $data->created_at->format('d M, Y h:i A'))
            ->addColumn('updated_at', fn($data) => $data->updated_at->format('d M, Y h:i A'))
    
            // Gray out row if not added (but button still clickable)
            ->setRowClass(function ($data) use ($affiliatePlanIds) {
                return in_array($data->id, $affiliatePlanIds) ? '' : 'opacity-50';
            })
    
            ->escapeColumns([])
            ->make(true);
    }

    


    public function syncAffiliateProductPlans(Request $request)
    {
        $affiliateId = $this->getId();

        if (!$affiliateId) {
            return response()->json(['status' => false, 'message' => 'Missing affiliate_id'], 400);
        }

        try {
            DB::beginTransaction();

            $globalPlans = ProductPlan::get();

            $created = 0;
            $updated = 0;

            foreach ($globalPlans as $plan) {
                

              $existing = AffiliateProductPlan::where('affiliate_id',$affiliateId)->where('product_plan_id',$plan->id)->first();

              if($existing){
                //update
                $existing->update([
                  'data_size_in_mb' => $plan->data_size_in_mb,
                  'validity_in_days' => $plan->validity_in_days,
                  'visibility' => $plan->visibility,
                  'visibility_from_admin' => $plan->visibility,
                  'public_visibility' => $plan->visibility,
                ]);
              }else{
                //create
                AffiliateProductPlan::create([
                  'affiliate_id' => $affiliateId,
                  'product_plan_id' => $plan->id,
                  'product_plan_name' => $plan->product_plan_name,
                  'user_level_1_profit' => $plan->aff_level_1_max_profit,
                  'user_level_2_profit' => $plan->aff_level_2_max_profit,
                  'user_level_3_profit' => $plan->aff_level_3_max_profit,
                  'user_level_4_profit' => $plan->aff_level_4_max_profit,
                  'user_level_5_profit' => $plan->aff_level_5_max_profit,
                  'user_level_6_profit' => $plan->aff_level_6_max_profit,
                  'data_size_in_mb' => $plan->data_size_in_mb,
                  'validity_in_days' => $plan->validity_in_days,
                  'visibility' => $plan->visibility
                ]);
              }
               
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Affiliate product plans synced successfully.",
                'created' => $created,
                'updated' => $updated,
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error syncing affiliate product plans: ' . $th->getMessage()
            ], 500);
        }
    }


    public function addAffiliateProductPlan(Request $request)
    {
        // logger('sss'); // maybe remove after debugging
    
        // $request->validate([
        //     'product_plan_id' => 'required|exists:product_plans,id',
        //     'product_plan_name' => 'nullable|string|max:255',
        // ]);
    
        try {
            $productPlan = ProductPlan::findOrFail($request->product_plan_id);
    
            $affiliate_id = $this->getId() ?? 1; // Ensure this fetches the correct affiliate
    
            $exists = AffiliateProductPlan::where('affiliate_id', $affiliate_id)
                        ->where('product_plan_id', $request->product_plan_id)
                        ->first();
    
            if ($exists) {
                return response()->json([
                    'status' => 'exists',
                    'message' => 'This product plan already exists under your affiliate plan.',
                ]);
            }
    
            $profit_max = $productPlan->profit_category == 'flat' ? 50 : 1;
            $affiliatePlan = AffiliateProductPlan::create([
                'affiliate_id' => $affiliate_id,
                'product_plan_name' => $request->product_plan_name ?? $productPlan->product_plan_name,
                'product_plan_id' => $productPlan->id,
                'data_size_in_mb' => $productPlan->data_size_in_mb,
                'validity_in_days' => $productPlan->validity_in_days,
                'user_level_1_profit' => $profit_max,
                'user_level_2_profit' => $profit_max,
                'user_level_3_profit' => $profit_max,
                'user_level_4_profit' => $profit_max,
                'user_level_5_profit' => $profit_max,
                'user_level_6_profit' => $profit_max,
                'user_level_1_commission' => 0,
                'user_level_2_commission' => 0,
                'user_level_3_commission' => 0,
                'user_level_4_commission' => 0,
                'user_level_5_commission' => 0,
                'user_level_6_commission' => 0,
                'commission_feature' => $productPlan->commission_feature,
                'upline_commission_option' => $productPlan->upline_commission_option,
                'upline_percentage_commission' => $productPlan->upline_percentage_commission,
                'upline_flat_commission' => $productPlan->upline_flat_commission,
                'upline_commission_cap' => $productPlan->upline_commission_cap,
                'visibility_from_admin' => 1,
                'visibility' => 1,
                'public_visibility' => 1,
            ]);
    
            return response()->json([
                'status' => 1,
                'message' => 'Product Plan successfully added to your affiliate product list.',
                'data' => $affiliatePlan,
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error adding affiliate product plan: ' . $e->getMessage(),
            ]);
        }
    }
  



   
    public function fetch_public_product_plans(Request $request){
      $data = ProductPlan::with(['product_plan_category.network','product_plan_category.product'])
      ->where('public_visibility',1)
      ->latest()->get();

      return DataTables::of($data)
      ->addIndexColumn()
      ->addColumn('DT_RowIndex',function($data){
        return $data->id;
      })
      ->addColumn('product_name',function($data){
        return $data->product_plan_category->product->product_name ?? '';
      })
      ->addColumn('product_plan_name',function($data){
        return $data->product_plan_name;
      })
      ->addColumn('network_name',function($data){
        return $data->product_plan_category->network->network_name ?? '';
      })
      ->addColumn('product_plan_category_id',function($data){
        return $data->product_plan_category->product_plan_category_name;
      })
      ->addColumn('data_size_in_mb',function($data){
        if($data->product_plan_category->product->slug == 'data'){
          return $data->data_size_in_mb;
        }else{
          return 'nil';
        }
      })
      ->addColumn('user_level_1_selling_price',function($data){
        if($data->product_plan_category->product->slug == 'airtime' || $data->product_plan_category->product->slug == 'utility_bills'){
          return number_format($data->user_level_1_selling_price,2). ' (% Discount)';
        }else{
          // return number_format($data->user_level_1_selling_price,2);
          return number_format($data->user_level_1_selling_price,2);

        }
      })
      ->addColumn('validity_in_days',function($data){
        return $data->validity_in_days;
      })
      ->escapeColumns([])
      ->make(true);
  }

    public function store(Request $request){
      
          $data['product_plan_name'] = $request->product_plan_name;
          // $data['product_id'] = $request->product_id;
          $data['product_plan_category_id'] = $request->product_plan_category_id; 
          $data['automation_product_plan_id'] = $request->id; ////planId
          $data['automation_id'] = $request->automation_id; ///
          $data['cost_price'] = $request->cost_price;
          $data['data_size_in_mb'] = $request->data_size_in_mb;
          $data['validity_in_days'] = $request->validity_in_day;
          $data['default_selling_price'] = $request->selling_price;
          $data['user_level_1_selling_price'] = $request->user_plan_1;
          $data['user_level_2_selling_price'] = $request->user_plan_2;
          $data['user_level_3_selling_price'] = $request->user_plan_3;
          $data['user_level_4_selling_price'] = $request->user_plan_4;
          $data['user_level_5_selling_price'] = NULL;
          $data['user_level_6_selling_price'] = NULL;
          $data['visibility'] = 1;
          $data['active_status'] = 1;
        
          if($request->validity_in_day == NULL || $request->validity_in_day == ''){
            return response()->json(['status'=>'-1', 'message'=> 'Error: Validity in days not set'  ]);
          }
          
          $fetch_product_plan_category = ProductPlanCategory::with('product')->where('id',$request->product_plan_category_id)->first();
          if(! $fetch_product_plan_category){
            return response()->json(['status'=>'-1', 'message'=> 'Error: Product category not set'  ]);
          }
          if($fetch_product_plan_category && ($fetch_product_plan_category->product->slug == 'airtime' || $fetch_product_plan_category->product->slug == 'utility_bills' ) ){
             if($request->user_plan_1 > 30 || $request->user_plan_2 > 30 || $request->user_plan_3 > 30 || $request->user_plan_4 > 30 ){
               return response()->json(['status'=>'-1', 'message'=> 'Error: Percentage discount cannot be greater than 30% for airtime and utility bills'  ]);
             }
          }
          
          $product_plan = ProductPlan::updateOrCreate([
            'automation_product_plan_id' => $request->id,
            'automation_id' => $request->automation_id,
          ],$data);

          // return response()->json(['status'=>'1', 'message'=>'successfully saved'. 'plan_id:'.$request->id.' auto_id: '.$request->automation_id. 'data:'.json_encode($data),  ]);
          return response()->json(['status'=>'1', 'message'=>'Plan was successfully saved' ]);

    
    }



    //single plan update
    public function update_plan2(Request $request){

        $validator = Validator::make($request->all(), [
          'product_plan_id' => 'required|max:255|exists:product_plans,id',
          'product_plan_name' => 'required|max:255',
          'cost_price' => 'required|numeric|gt:0',
          // 'data_size_in_mb' => 'required|numeric',
          'validity_in_days' => 'required|numeric',
          'default_selling_price' => 'required|numeric',
          'user_level_1_selling_price' => 'required|numeric',
          'user_level_2_selling_price' => 'required|numeric',
          'user_level_3_selling_price' => 'required|numeric',
          'user_level_4_selling_price' => 'required|numeric'

        ]);

        if ($validator->stopOnFirstFailure()->fails()) {
          return response()->json(['status'=> false,'message'=> $validator->errors()->first()]);
        }

       
        if(auth()->user()->email != 'adebsholey4real@gmail.com'){
          return response()->json([
            'status' => false,
            'message'=> 'not authorized',
           ]);
        }
         
         $plan_id = $request->product_plan_id;
         $cost_price = $request->cost_price;
         $visibility = $request->visibility;
         $default_selling_price =  $request->default_selling_price;
         $user_level_1_selling_price =  $request->user_level_1_selling_price;
         $user_level_2_selling_price =  $request->user_level_2_selling_price;
         $user_level_3_selling_price =  $request->user_level_3_selling_price;
         $user_level_4_selling_price =  $request->user_level_4_selling_price;
         $data_size_in_mb =  $request->data_size_in_mb;
         $product_plan_name =  $request->product_plan_name;
         $validity_in_days =  $request->validity_in_days;


        //  $user_level_1_commission =  $request->user_level_1_commission;
        //  $user_level_2_commission =  $request->user_level_2_commission;
        //  $user_level_3_commission =  $request->user_level_3_commission;
        //  $user_level_4_commission =  $request->user_level_4_commission;
        //  $commission_feature =  $request->commission_feature;


         ProductPlan::where('id',$plan_id)->update([
          "product_plan_name" =>  $product_plan_name,
          "cost_price" =>  $cost_price,
          "visibility" =>  $visibility,
          "default_selling_price" =>  $default_selling_price,
          "user_level_1_selling_price" =>  $user_level_1_selling_price,
          "user_level_2_selling_price"=>  $user_level_2_selling_price,
          "user_level_3_selling_price" =>  $user_level_3_selling_price,
          "user_level_4_selling_price" =>  $user_level_4_selling_price,
          // "user_level_1_commission" =>  $user_level_1_commission,
          // "user_level_2_commission"=>  $user_level_2_commission,
          // "user_level_3_commission" =>  $user_level_3_commission,
          // "user_level_4_commission" =>  $user_level_4_commission,
          // "commission_feature" =>  $commission_feature,
          "data_size_in_mb" =>  $data_size_in_mb,
          "validity_in_days" =>  $validity_in_days,
         ]);
     

         sleep(2);
  
         return response()->json([
          'status' => true,
          'message'=> 'successfully updated plan',
         ]);
   }



    //automation update
    public function update(Request $request){
       
      //  return $request->all();
      $validator = Validator::make($request->all(), [
         'product_plan_id' => 'required|max:255|exists:product_plans,id',
         'product_plan_name' => 'required|max:255',
         'product_plan_category_idd' => 'required',
         'automation_product_plan_id' => 'required',
         'cost_price' => 'required|numeric|gt:0',
         'data_size_in_mb' => 'required|numeric',
         'validity_in_days' => 'required|numeric',
         'default_selling_price' => 'required|numeric',
         'user_level_1_selling_price' => 'required|numeric',
         'user_level_2_selling_price' => 'required|numeric',
         'user_level_3_selling_price' => 'required|numeric',
         'user_level_4_selling_price' => 'required|numeric',
         'upline_commission_option' => ['required',Rule::in(['flat','percentage'])],
         'upline_percentage_commission' => 'required|numeric',
         'upline_flat_commission' => 'required|numeric',
         'upline_commission_cap' => 'required|numeric',
       ]);

       if ($validator->stopOnFirstFailure()->fails()) {
         return redirect()->back()->withErrors($validator)->withInput();
       }

       if($request->upline_percentage_commission > 20){
          Session::flash('failure','Upline percentage commission cannot be greater than 20');
          return redirect()->back();
        }

       

       $data = $validator->validated();
       unset($data['product_plan_category_idd']);
       unset($data['product_plan_id']);
       $data['product_plan_category_id'] = $request->product_plan_category_idd;
      
      //  dd($data);

       $create_product_plans = ProductPlan::where('id',$request->product_plan_id)->update($data);
 
       if($create_product_plans){
         Session::flash('success','Product plan successfully updated');
       }else{
         Session::flash('failure','Error occurred while updating product plan');
       }
 
       return redirect()->route('admin.product_plans.index');
   }
}
