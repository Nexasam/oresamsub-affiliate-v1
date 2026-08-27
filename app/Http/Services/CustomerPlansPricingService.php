<?php

namespace App\Http\Services;

use App\Models\User;
use App\Models\Network;
use App\Models\Product;
use App\Models\UserPlan;
use App\Models\ProductPlan;
use App\Models\AffiliateUserPlan;
use App\Models\PlanProfitSetting;
use App\Models\ProductPlanCategory;
use App\Models\AffiliateProductPlan;
use function GuzzleHttp\json_encode;
use Illuminate\Support\Facades\Hash;
use App\Models\ProductPlanCustomPricing;
use App\Models\AffiliateProductPlanCategory;
use App\Models\Affiliate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CustomerPlansPricingService{

    public function fetch_plans_with_pricing($data){
        $user_details = $data['user'];
        $affiliate = Affiliate::with('processingProfile')->find($user_details?->affiliate_id);
        $isMultiParent = $affiliate
            && $affiliate->parent_business_id
            && $affiliate->processingProfile?->processing_engine === 'multi_parent';
    
        $product_plans = AffiliateProductPlan::with('product_plan.product_plan_category.network','product_plan.product_plan_category.product')
        ->where('visibility', 1)
        ->when($isMultiParent, fn ($query) => $query->customerAvailable())
        ->orderByRaw('CASE WHEN CAST(data_size_in_mb AS UNSIGNED) < 500 THEN 1 ELSE 0 END') // Push <500MB to bottom
        ->orderByRaw('CAST(data_size_in_mb AS UNSIGNED)') // Then order by size
        ->orderByRaw('CAST(validity_in_days AS UNSIGNED) DESC') // Then by validity
        ->get();
        
        $product_planss = [];
        $dat = [];

        if(count($product_plans) >= 1){

            foreach($product_plans as $key=>$product_plan){
                $dat['plan_details'] = $product_plan;
                $dat['product_id'] = $product_plan->product_plan->product_plan_category->product->id;
                $dat['user'] = $user_details;
                $dat['amount'] = 0;
                try {
                    $generalres = $this->get_customer_price_per_plan($dat);
                } catch (ValidationException $exception) {
                    Log::warning('Customer pricing page skipped plan with invalid pricing.', [
                        'affiliate_id' => $affiliate?->id,
                        'affiliate_product_plan_id' => $product_plan->id,
                        'product_plan_id' => $product_plan->product_plan_id,
                        'service' => $product_plan->product_plan?->product_plan_category?->product?->slug,
                        'network' => $product_plan->product_plan?->product_plan_category?->network?->network_name,
                        'errors' => $exception->errors(),
                    ]);
                    continue;
                }

                $selling_price = $generalres['sellingprice'];
                $rate = $generalres['rate'];
                $network = $generalres['network'];
                $cost_price = $generalres['cost_price'];

                $outputKey = count($product_planss);
                $product_planss[$outputKey]['rate'] = $rate;
                $product_planss[$outputKey]['category'] = Product::where('id',$product_plan->product_plan->product_plan_category->product->id)->value('slug');
                $product_planss[$outputKey]['plan_category'] = $product_plan->product_plan->product_plan_category->product_plan_category_name;
                $product_planss[$outputKey]['plan_category_id'] = $product_plan->product_plan->product_plan_category->id;
                $product_planss[$outputKey]['rate'] = $rate;
                $product_planss[$outputKey]['network'] = $network;
                $product_planss[$outputKey]['cost_price'] = $cost_price;
                $product_planss[$outputKey]['product_plan_id'] = $product_plan->id;
                $product_planss[$outputKey]['selling_price'] = $selling_price;
                $product_planss[$outputKey]['product_plan_name'] = $product_plan->product_plan_name;
                $product_planss[$outputKey]['data_size_in_mb'] = $product_plan->data_size_in_mb;
                $product_planss[$outputKey]['validity_in_days'] = $product_plan->validity_in_days;
                            
            }

        }

        if ($product_planss === []) {
            $product_planss[0]['rate'] = null;
            $product_planss[0]['category'] = null;
            $product_planss[0]['network'] = null;
            $product_planss[0]['product_plan_id'] = null;
            $product_planss[0]['selling_price'] = null;
            $product_planss[0]['product_plan_name'] = null;
            $product_planss[0]['data_size_in_mb'] = null;
            $product_planss[0]['validity_in_days'] = null; 
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
        $data_size_in_mb = $product_plan->product_plan->data_size_in_mb;
        $product_id = $data['product_id'];
        $user_details = $data['user'];
        $amount = $data['amount'] ?? 0;
        $slug = Product::select('slug')->where('id',$product_id)->first()->slug;

        $affiliate = Affiliate::with('processingProfile')->find($user_details?->affiliate_id);
        $isMultiParent = $affiliate
            && $affiliate->parent_business_id
            && $affiliate->processingProfile?->processing_engine === 'multi_parent';

        if ($isMultiParent) {
            $customerLevel = (int) (AffiliateUserPlan::whereKey($user_details->user_plan_id)->value('plan_level') ?? 1);
            $network = $product_plan->product_plan?->product_plan_category?->network?->network_name;

            if (in_array($slug, ['airtime', 'utility_bills'], true)) {
                $margin = (string) ($product_plan->{"user_level_{$customerLevel}_profit"} ?? 0);

                return [
                    'status' => 1,
                    'plan_level' => $customerLevel,
                    'network' => $network ?? ($slug === 'utility_bills' ? 'PREPAID' : null),
                    'cost_price' => null,
                    'rate' => 'percent',
                    'sellingprice' => $margin,
                    'message' => $margin,
                ];
            }

            $resolved = app(DataPlansService::class)->get_customer_price_per_plan([
                'plan_details' => $product_plan,
                'product_id' => $product_id,
                'user' => $user_details,
                'amount' => $amount,
            ]);

            return [
                'status' => 1,
                'plan_level' => $customerLevel,
                'network' => $network,
                'cost_price' => null,
                'rate' => 'flat',
                'sellingprice' => $resolved['message'],
                'message' => $resolved['message'],
            ];
        }

        // logger()->info('wetin dey hhappen here: '.json_encode($data));


        // AffiliateUserPlan::select('id')->where('plan_level',1)->first()->id ??
        $user_plan_id = $user_details == NULL ? 1 : $user_details->user_plan_id; //default to level 1 if not set
        // $user_id = $user_details->id;
        $user_level = AffiliateUserPlan::select('plan_level')->where('id',$user_plan_id)->first();
        $affiliate_parent_cost_price_level = session('affiliate')->parent_plan_level; #u should use the parent plan level instead
        $cost_price_col = "cost_price_$affiliate_parent_cost_price_level";
        $cost_price = $data['plan_details']->product_plan->$cost_price_col;
        $network_id = $product_plan->product_plan->product_plan_category->network->id ?? null;

        $plan_level = $user_level->plan_level  ??  1;
        $profit_col = "user_level_{$plan_level}_profit";

        if($slug == 'data' || $slug == 'cable_subscription'){
            $profit_value = $product_plan->$profit_col ?? 50;
            $selling_price = $cost_price + abs($profit_value);
            $augmentsp = $cost_price + 50;
            $rate = 'flat';
            $network = $network_id == null ? null : Network::where('id',$network_id)->value('network_name');

        }else{
            //airtime and electricity for now
            $profit_value = $product_plan->$profit_col ?? 1; //percent
            $rate = 'percent';
            $network = $network_id == null ? null : Network::where('id',$network_id)->value('network_name');

    
            $actual_discount_value = ceil(($profit_value / 100) * $amount);  
            $discounted_selling_price = $amount - abs($actual_discount_value);
            $selling_price = $profit_value; //this is from the system, not applicable for airtime


            $augment_discount = ceil((1 / 100) * $amount);  
            $augmentsp = $amount - abs($augment_discount);
          
        }

        if($network == null){
            //it means its either cable or electricity
            if($slug == 'utility_bills'){
                $network = 'PREPAID';
            }

            if ($slug == 'cable_subscription') {
                $planname = strtolower($data['plan_details']->product_plan->product_plan_name);
            
                if (str_starts_with($planname, 'gotv')) {
                    $network = 'GOTV';
                } elseif (str_starts_with($planname, 'dstv')) {
                    $network = 'DSTV';
                } elseif (str_starts_with($planname, 'startimes')) {
                    $network = 'STARTIMES';
                } else {
                    $network = 'UNKNOWN';
                }
            
                // Example:
                // return response()->json(['network' => $network, 'plan' => $planname]);
            }
            

        }


        return [
            'status' => 1,
            'plan_level' => $plan_level,
            'network' => $network,
            'cost_price' => $cost_price,
            'rate' => $rate,
            'sellingprice' => $selling_price ?? $augmentsp,
            'message' => $selling_price ?? $augmentsp
        ];
    }




}
