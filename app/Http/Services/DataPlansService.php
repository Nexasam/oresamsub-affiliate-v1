<?php

namespace App\Http\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\ProductPlan;
use App\Models\AffiliateUserPlan;
use App\Models\PlanProfitSetting;
use App\Models\ProductPlanCategory;
use App\Models\AffiliateProductPlan;
use Illuminate\Support\Facades\Hash;
use App\Models\ProductPlanCustomPricing;
use App\Models\AffiliateProductPlanCategory;
use function GuzzleHttp\json_encode;

class DataPlansService{

    public function fetch_user_data_plans($data){
        $user_details = $data['user'];
        $network_id = $data['network_id'];
        $product_plan_category_id = $data['product_plan_category_id'] ?? NULL;
        $product_id = $data['product_id'];
        $amount = $data['amount'] ?? 0;
        $is_api = $data['is_api'] ?? NULL;

        $slug = Product::select('slug')->where('id',$product_id)->first()->slug;

        //you need to note later if the user selelcted a type:
        if($product_plan_category_id == NULL){
          
            $product_plan_categories_id_arr = AffiliateProductPlanCategory::where('product_id',$product_id)
            ->when(!empty($network_id), function($query) use ($network_id) {
                            $query->where('network_id',$network_id);
            })
            ->pluck('plan_category_id')
            ->toArray();

            $product_plans_arr = ProductPlan::whereIn('product_plan_category_id', $product_plan_categories_id_arr)
            ->where('visibility', 1)
            ->pluck('id')
            ->toArray();

            logger('papapaaaaa: '.json_encode($product_plan_categories_id_arr));
            
        }else{
   
            // $product_plan_categories_id_arr = [$product_plan_category_id];
            // where('product_id',$product_id)
            $product_plan_categories_id_arr = AffiliateProductPlanCategory::where('id',$product_plan_category_id)
            ->when(!empty($network_id), function($query) use ($network_id) {
                            $query->where('network_id',$network_id);
            })
            ->pluck('plan_category_id')
            ->toArray();


            $product_plans_arr = ProductPlan::whereIn('product_plan_category_id', $product_plan_categories_id_arr)
            ->where('visibility', 1)
            ->pluck('id')
            ->toArray();
            logger('mamamaaaa: '.json_encode($product_plans_arr));

        }


        $product_plans = AffiliateProductPlan::with('product_plan')->whereIn('product_plan_id', $product_plans_arr)
        ->where('visibility', 1)
        ->orderByRaw('CASE WHEN CAST(data_size_in_mb AS UNSIGNED) < 500 THEN 1 ELSE 0 END') // Push <500MB to bottom
        ->orderByRaw('CAST(data_size_in_mb AS UNSIGNED)') // Then order by size
        ->orderByRaw('CAST(validity_in_days AS UNSIGNED) DESC') // Then by validity
        ->get();
        // logger('pp check: '.json_encode($product_plans));

        
        $product_planss = [];
        $dat = [];

        if(count($product_plans) >= 1){

            foreach($product_plans as $key=>$product_plan){
           
                $dat['product_id'] = $product_id;
                $dat['user'] = $user_details;
                $dat['plan_details'] = $product_plan;
                $dat['network_id'] = $network_id;
                $dat['amount'] = $amount;
                $generalres = $this->get_customer_price_per_plan($dat);
                $selling_price = $generalres['message'];
                $plan_level = $generalres['plan_level'];
    
             
                if($is_api != NULL){
                  //api route
                  $product_planss[$key]['plan_id'] =  (int)$product_plan->api_id ?? NULL; //api for api calls
                  $product_planss[$key]['cost_price'] = $selling_price; //their cost price will be selling price 
                 }else{
                  //likely web/mobile route
                  $product_planss[$key]['product_plan_id'] = $product_plan->id;
                  $product_planss[$key]['selling_price'] = $selling_price;
                  $product_planss[$key]['automation_id'] = $product_plan->automation_id;  
                }


                $product_planss[$key]['product_plan_name'] = $product_plan->product_plan_name;
                $product_planss[$key]['data_size_in_mb'] = $product_plan->data_size_in_mb;
                $product_planss[$key]['validity_in_days'] = $product_plan->validity_in_days; 
                             
            }

        }else{
            $product_planss[0]['cost_price'] = NULL;
            $product_planss[0]['product_plan_id'] = NULL;
            $product_planss[0]['api_id'] = NULL;
            $product_planss[0]['selling_price'] = NULL;
            $product_planss[0]['product_plan_name'] = NULL;
            $product_planss[0]['data_size_in_mb'] = NULL;
            $product_planss[0]['validity_in_days'] = NULL;    
            $product_planss[0]['automation_id'] = NULL;  
        }

        if($is_api != NULL){
            return [
                'status' => 1,
                'message' => $product_planss,
                'plans' => $product_planss,
            ];
        }
        
        $data_sizes = collect($product_planss)
        ->pluck('data_size_in_mb')
        ->unique()
        ->sort()
        ->values()
        ->toArray();
        return [
            'status' => 1,
            'message' => $product_planss,
            'plans' => $product_planss,
            'sizes' => $data_sizes ?? [],
            'plan_level' => $plan_level ?? 1
        ];
    }

    public function get_customer_price_per_plan($data){
        $product_plan = $data['plan_details'];
        // $cost_price = $data['plan_details']->product_plan->cost_price;
        $data_size_in_mb = $data['plan_details']->product_plan->data_size_in_mb ?? '';
        $network_id = $data['network_id'] ?? '';
        $product_id = $data['product_id'];
        $user_details = $data['user'];
        $amount = $data['amount'] ?? 0;
        $slug = Product::select('slug')->where('id',$product_id)->first()->slug;


        $user_plan_id = $user_details->user_plan_id;
        $user_id = $user_details->id;
        $user_level = AffiliateUserPlan::select('plan_level')->where('id',$user_plan_id)->first();
        $affiliate_parent_cost_price_level = session('affiliate')->parent_plan_level; #u should use the parent plan level instead
        $cost_price_col = "cost_price_$affiliate_parent_cost_price_level";
        $cost_price = $data['plan_details']->product_plan->$cost_price_col;

        $plan_level = $user_level->plan_level  ??  1;
        $profit_col = "user_level_{$plan_level}_profit";

        if($slug == 'data' || $slug == 'cable_subscription'){
            $profit_value = $product_plan->$profit_col ?? 50;
            $selling_price = $cost_price + abs($profit_value);
        //    logger('whhahts cp: '.$profit_col);

            $augmentsp = $cost_price + 50;
        }else{
            //airtime and electricity for now
            $profit_value = $product_plan->$profit_col ?? 1; //percent
            
            $actual_discount_value = ceil(($profit_value / 100) * $amount);  
            $discounted_selling_price = $amount - abs($actual_discount_value);
            $selling_price = $discounted_selling_price; //this is from the system, not applicable for airtime


            $augment_discount = ceil((1 / 100) * $amount);  
            $augmentsp = $amount - abs($augment_discount);
          
        }


        return [
            'status' => 1,
            'plan_level' => $plan_level,
            'message' => $selling_price ?? $augmentsp
        ];
    }

}