<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Network;
use App\Models\Product;
use App\Models\UserPlan;
use App\Models\Automation;
use App\Models\ProductPlan;
use App\Models\Transaction;
use App\Models\SiteTemplate;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;
use App\Models\UserBulkDataWallet;
use Illuminate\Support\Facades\DB;
use App\Models\ProductPlanCategory;
use App\Models\AffiliateProductPlan;
use App\Models\BulkDataProductPlans;
use App\Models\UserBulkDataPurchase;
use App\Http\Services\DataPlansService;
use Illuminate\Support\Facades\Validator;
use App\Models\AffiliateProductPlanCategory;
use App\Services\Automation\AutomationLogic;
use App\Services\Automation\VtpassAutomation;
use App\Services\Automation\PaultechsAutomation;
use App\Traits\Dashboard\UserDashboardDataTrait;
use App\Services\Automation\MegaSubPlugAutomation\VendData;
use App\Services\Automation\MegaSubPlugAutomation\MegaSubCableTV;
use App\Http\Services\Api\v1\VendorUsersApi\Products\ProductsService;
use App\Services\Automation\MsOrgGroupAutomation\MsOrgGroupAutomation;

class CableSubscriptionController extends Controller
{
    use UserDashboardDataTrait;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
    }


    public function fetch_cable_transactions(Request $request){
   
        // date('Y-m-d', strtotime('-10 days'))
        $date_from = $request->date_from ?? '';

        // date('Y-m-d')
        $date_to= $request->date_to ?? '';

        $product_plan_category_filter = $request->product_plan_category_filter ?? '';
        
        $smart_card = $request->smart_card_number ?? '';
        
        $limit = $request->limit ?? 2000;

       
        $data = Transaction::when(!empty($date_from) && !empty($date_to) , function ($query) use ($date_from,$date_to){
            $date_to = date('Y-m-d', strtotime('+1 day', strtotime($date_to)));
            $query->where('created_at','>=',$date_from)->where('created_at','<=',$date_to);
        })->when(!empty($product_plan_category_filter) , function ($query) use ($product_plan_category_filter){
            $product_plan_ids = ProductPlan::where('product_plan_category_id',$product_plan_category_filter)->pluck('id');
            $query->whereIn('product_plan_id',$product_plan_ids);
        })->when(!empty($smart_card) , function ($query) use ($smart_card){
          $query->where('smart_card_number',$smart_card);
        })
        ->where('transaction_category','cable_subscription')
        ->where('user_id',auth()->id())
        ->with(['user','product_plan'])->latest()->limit($limit)->get();
        // return $data;


      return DataTables::of($data)
      ->addIndexColumn()
      ->addColumn('DT_RowIndex',function($data){
        return $data->id;
        })
        // ->addColumn('user_id',function($data){
        //     $first_name = $data->user->first_name  ?? 'nil';
        //     $last_name = $data->user->last_name  ?? 'nil';
        //     $phone_number = $data->user->phone_number  ?? 'nil';
        //     $user_details =  $first_name.'<br>'.$last_name.'<br>'.$phone_number.'<br>';     
        //     return $user_details;
        // })
        ->addColumn('wallet_category',function($data){
            $wallet_category = $data->wallet_category == 'main_wallet' ?  'MAIN' : 'DATA_WALLET';
            return $wallet_category;
        })
        ->addColumn('plan_details',function($data){
            if($data->product_plan != NULL){
               
                $dataa =  $data->product_plan->product_plan_name.'<br>';
                $dataa .=  $data->product_plan->product_plan_category->product_plan_category_name.'<br>';
                if($data->transaction_category == 'cable_subscription'){
                    $dataa .=  'Smart Card No: '.$data->smart_card_number.'<br>';
                }
                if($data->transaction_category == 'utility_bills'){
                    $dataa .=  'Metre No: '.$data->metre_number.'o<br>';
                }
                if($data->transaction_category == 'data'){
                    $dataa .= number_format($data->product_plan->data_size_in_mb ?? '0') .' MB';
                }

            }else{
                $dataa = 'NIL';
            }
            return $dataa;
        })
     
        ->addColumn('transaction_category',function($data){
            $transaction_category = $data->transaction_category;
            return $transaction_category;
        })
        // ->addColumn('response',function($data){
        //     return  '<span style="white-space: normal;word-wrap: break-word;word-break: normal;width:auto">'.$data->user_screen_message.'</span>';
        //     // return $user_screen_message;
        // })
        ->addColumn('phone_number',function($data){
            return $data->phone_number;
        }) 
        ->addColumn('smart_card_number',function($data){
            return $data->smart_card_number ?? '-';
        }) 
       ->addColumn('amount',function($data){
        return '&#8358;'.(number_format($data->amount,2));
        }) 
        ->addColumn('balance_before',function($data){
            return $data->wallet_category == 'main_wallet' ? '₦'.number_format($data->balance_before,2) : number_format($data->balance_before).'MB';

        })  
        ->addColumn('data_size',function($data){
         $data_size = number_format($data->product_plan->data_size_in_mb ?? '0') .' MB';
         return $data_size;
        })  
        ->addColumn('balance_after',function($data){
        return $data->wallet_category == 'main_wallet' ? '₦'.number_format($data->balance_after,2) : number_format($data->balance_after).'MB';
        })  
        ->addColumn('status',function($data){
            if($data->status == 1){
                $status_display = '<span class="badge bg-success text-white">Success</span>';
            }
            elseif($data->status == -1){
                $status_display = '<span class="badge bg-red-300 text-white">Unsuccessful</span>';
            }
            elseif($data->status == 0){
                $status_display = '<span class="badge bg-warning text-white">Pending</span>';
            }
            elseif($data->status == 2){
                $status_display = '<span class="badge bg-primary text-white">Refunded</span>';
            }
            elseif($data->status == 3){
                $status_display = '<span class="badge bg-gray text-white">Processing</span>';
            }else{
                $status_display = '<span class="badge bg-gray text-white">Unknown</span>';
            }
           return $status_display;  
        }) 
        ->addColumn('created_at',function($data){
            return $data->created_at;
        }) 
        ->addColumn('action',function($data){
            $route = route('transactions.transaction_details',$data->id);
            $actionBtn = '<a href="'.$route.'" type="button" class="hs-dropdown-toggle ti-btn ti-btn-primary" data-hs-overlay="#hs-vertically-centered-scrollable-modal'.$data->email.'">
            Details
            </a>';
            return $actionBtn;
        })
        
        ->escapeColumns([])
        ->make(true);


       
    }

    /**
     * Show the form for creating a new resource.
     */
    public function buy_cable_subscription()
    {

        $dataa = $this->get_user_dashboard_data();
        $data = [...$dataa];
       
        $product = Product::where('slug','cable_subscription')->first(); //TODO: have enums that gets the id later
        $data['product'] = $product;

        $product_plan_categories = AffiliateProductPlanCategory::where('product_id',$product->id)->get(); //TODO: have enums that gets the id later
        $data['product_plan_categories'] = $product_plan_categories;
        

        $user_details = auth()->user();
        $user_id = $user_details->id;
        // dd($user_id);

        //data txns list
        $cable_transactions = Transaction::with('user')->where('transaction_category','cable_subscription')
        ->where('user_id',$user_id)
        ->latest()
        ->get();
        $data['cable_transactions'] = $cable_transactions;
        $data['user_details'] = $user_details;

        $siteTemplate = SiteTemplate::first();
        if(! $siteTemplate || $siteTemplate->template_name == 'template_1'){
            return view('user.cabletv.buy_cable_tv')->with($data);
        }

        // dd($data);
        return view('template2.user.cabletv.buy_cable_tv')->with($data);
    }

    public function validate_smart_card_number(Request $request){
         //call the automation involved
         $plan_details = ProductPlan::with('product_plan_category','automation')
         ->where('visibility',1)
         ->where('id',$request->plan_id)
         ->first();
      
         if(! $plan_details){
             return [
                 'status' => -1,
                 'user_message' => 'An error occurred while processing this transaction. Please try again or reach out to support',
                 'admin_message' => 'Wrong plan Id',
             ];
         }
 
         $user_id = auth()->id();
        //  $automation_slug = $plan_details->automation->slug;
        //  $automation_group = $plan_details->automation->automation_group;

        $automation_slug = 'megasubplug';
         $automation_group = 'nil';
         if($automation_slug == 'megasubplug'){
             $validate_metre_number = (new MegaSubCableTV(smart_card_number: $request->smart_card_number, plan_id: $request->plan_id, user_id: $user_id))->validateSmartCardNumber();
             return $validate_metre_number;
         }else if($automation_slug == 'vtpass'){
            $dataa['smart_card_number'] = $request->smart_card_number;
            $dataa['plan_id'] = $request->plan_id;
            $dataa['user_id'] = $user_id;
            $validate_smart_card_number = (new VtpassAutomation($dataa))->validateSmartCard();
            return $validate_smart_card_number;
        }else if($automation_slug == 'paultechs'){
            $token = $plan_details->automation->api_public_key;
            $dataa['automation_id'] =  $plan_details->automation->id;
            $dataa['smart_card_number'] = $request->smart_card_number;
            $dataa['plan_id'] = $request->plan_id;
            $dataa['token'] = $token;
            $dataa['user_id'] = $user_id;
            $dataa['url'] = $plan_details->automation->cable_url;
            $validate_smart_card_number = (new PaultechsAutomation($dataa))->validateSmartCard();
            return $validate_smart_card_number;
        }else if($automation_group == 'msorg'){
            $token = $plan_details->automation->api_public_key;
            $dataa['automation_id'] =  $plan_details->automation->id;
            $dataa['smart_card_number'] = $request->smart_card_number;
            $dataa['plan_id'] = $request->plan_id;
            $dataa['token'] = $token;
            $dataa['user_id'] = $user_id;
            $dataa['url'] = $plan_details->automation->cable_url;
            $validate_smart_card_number = (new MsOrgGroupAutomation($dataa))->validateSmartCard();
            return $validate_smart_card_number;
        }
        return 'Nil';

    }


        /**
     * Show the form for creating a new resource.
     */
    public function buy_cable_subscription_by_plan_category($id)
    {

        $plan_category = ProductPlanCategory::with('product','network','automation')->where('id',$id)->first();
        
        $product_plans = ProductPlan::where('automation_id',$plan_category->automation->id)
        ->where('visibility',1)
        ->where('product_plan_category_id',$id)->get();

        $product = Product::where('slug','cable_subscription')->first(); //TODO: have enums that gets the id later
        $product_plan_categories = ProductPlanCategory::where('product_id',$product->id)->get(); //TODO: have enums that gets the id later
        $data['product_plan_categories'] = $product_plan_categories;
        
      
        $user_details = auth()->user();
        $user_id = $user_details->id;
        $user_plan_id = $user_details->user_plan_id;
       
        $product_planss = [];
        $counter = 0;

        $user_level = UserPlan::select('plan_level')->where('id',$user_plan_id)->first();
        $plan_level = $user_level->plan_level;
        foreach($product_plans as $product_plan){

            $user_level_selling = "user_level_".$plan_level."_selling_price";
            // $user_level_selling = "{user_level_$user_level_selling_price}";
            $selling_price = $product_plan->$user_level_selling;
            if($product_plan){
                $counter++;
                $product_planss[$counter]['product_plan_id'] = $product_plan->id;
                $product_planss[$counter]['selling_price'] = $selling_price;
                $product_planss[$counter]['product_plan_name'] = $product_plan->product_plan_name;
                $product_planss[$counter]['data_size_in_mb'] = $product_plan->data_size_in_mb;
                $product_planss[$counter]['validity_in_days'] = $product_plan->validity_in_days;    
                $product_planss[$counter]['automation_id'] = $product_plan->automation_id;    
            }
        }

      
        //data txns list
        $cable_transactions = Transaction::with('user')->where('transaction_category','cable_subscription')
        ->where('user_id',$user_id)
        ->latest()
        ->get();
        $data['cable_transactions'] = $cable_transactions;
        $data['user_details'] = $user_details;
        $data['product_plans'] = $product_planss;
        $data['plan_category'] = $plan_category;
        
        // dd($data);
        return view('user.cabletv.buy_cable_tv_by_plan_category')->with($data);
    }

    
    function generateTxnReference($prefix, $userUuid) {
        // Take a shortened, hashed version of UUID (to avoid super long refs)
        $userHash = substr(hash('sha1', $userUuid), 0, 8); 
        
        $timestamp = date('Ymd_His_u'); // precise timestamp with microseconds
        $random    = bin2hex(random_bytes(3)); // 6 random hex chars
        
        return "{$prefix}_{$timestamp}_{$userHash}_{$random}";
    }



    /**
     * Store a newly created resource in storage.
     */
    public function buy_cable_subscription_action(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'smart_card_number' => 'required',
            'validation_customer_name' => 'required',
            'cable_product_plan_category_id' => 'nullable', #fix
            'cable_product_plan_id' => 'required',
            'wallet_category' => 'required',
            'no_of_slots' => 'required',
            'pin' => ['required','string','regex:/^\d{4,5}$/'],
        ]);

        // logger('PP'.$request->all());
        // return response()->json(['status'=>'-1', 'message'=>$validator->errors()->first(),'data' => $request->all() ]);

        if ($validator->stopOnFirstFailure()->fails()) {
            return response()->json(['status'=>'-1', 'message'=>$validator->errors()->first(),'data' => $request->all() ]);
        }

        $data1['days_count'] = [1,7,30];
        $data1['user_id'] = auth()->id();
        $check_purchase_limit =  ProductsService::check_purchase_limit($data1);
        if($check_purchase_limit['status'] == -1){
            return response()->json(['status'=>'-1', 'message'=>$check_purchase_limit['message'] ]);
        }

      

        $success = 0;
        $failure = 0;
        $status = 0;
        $message = 'Pending';
        $display_results = [];
        $no_of_slots = $request->no_of_slots; //to be adjusted later
        

        $user_details = auth()->user();
        if(! $user_details){
            //end session and redirect to login
            redirect(url('/login'));
            return response()->json(['status'=>'-1', 'message'=>'please logout and login again' ]);
        }
        $user_id = $user_details->id;
        $txn_reference = $this->generateTxnReference('CABLE', $user_id);


        $plan_details = AffiliateProductPlan::where('id',$request->cable_product_plan_id)->where('visibility',1)->first();
        if(! $plan_details){
            //end session and redirect to login
            redirect(url('/login'));
            return response()->json(['status'=>'-1', 'message'=>'plan details not found' ]);
        }
        $automation_id = $plan_details->automation_id;
        // $data_value_mb = $plan_details->data_size_in_mb ?? 0;

        $plan_category_details = AffiliateProductPlanCategory::where('id',$request->cable_product_plan_category_id)->first();
        if(! $plan_category_details){
            //end session and redirect to login
            redirect(url('/login'));
            return response()->json(['status'=>'-1', 'message'=>'plan category details not found' ]);
        }


        if($user_details->pin != $request->pin){
            //end session and redirect to login
            return response()->json(['status'=>'-1', 'message'=>'Incorrect PIN' ]);
        }


         //////////getting amount first
         $product_id = $plan_details->product_plan->product_plan_category->product->id;
         $slug = Product::select('slug')->where('id',$product_id)->first()->slug;
         $plan_id = $plan_details->product_plan->api_id;
         $ddar['plan_details'] = $plan_details;
         $ddar['product_id'] = $product_id;
         $ddar['user'] = $user_details;
         $ddar['amount'] = 0;
         $ddar['slug'] = $slug;
         $fetch_data_plan = (new DataPlansService())->get_customer_price_per_plan($ddar); #change tthis to take all plans not just data
         $amount = $fetch_data_plan['message'];
         /////////getting amount end

        $user_id = $user_details->id;
        $smart_card_number = $request->smart_card_number;
     

        DB::beginTransaction();
        try{

              ////validate wallet
                        if($request->wallet_category == 'main_wallet'){
                            $wallet_before = $user_details->main_wallet;
                            $total_amount =  $no_of_slots * $amount;
                            if($total_amount > $wallet_before || $wallet_before < 0){
                                return response()->json(['status'=>'-1', 'message'=>'Insufficient wallet balance' ]);
                            }
                    
                            //calling the actual vending via the automation:
                            $automation_details = Automation::where('id',$automation_id)->first();            
                            //TODO: candidate for separation
                       
                             //TODO: candidate for separation
                             for($i = 1; $i <= $no_of_slots; $i++ ){
                                $arrrequest = [
                                    'plan' => $plan_id,
                                    'validation_customer_name' => $request->validation_customer_name,
                                    // 'validated_address' => $validated,
                                    'smart_card_number' => $smart_card_number,
                                    'reference' => $txn_reference
                                ];
                                $arrrequest_json = json_encode($arrrequest);
                                logger('REQ: '.$arrrequest_json);

                                $curl = curl_init();
                                curl_setopt_array($curl, array(
                                CURLOPT_URL => 'https://oresamsub.com/api/v1/user/buy_cable_tv',
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'POST',
                                CURLOPT_POSTFIELDS =>$arrrequest_json,
                                CURLOPT_HTTPHEADER => array(
                                    'Authorization: Token 01a472d9582fc1eb9b22cc2f48badf2eb8c0573f',
                                    'Content-Type: application/json',
                                    'Accept: application/json'
                                ),
                                ));
                                $response = curl_exec($curl);
                                curl_close($curl);
                                $buy_electricity_subscription =  $response;
                                $purchase_dec = json_decode($buy_electricity_subscription,true);
                                $status = $purchase_dec['status'];
                                
                                if($status == 1){
                                   
                                    return [
                                        'status' => 1,
                                        'retry_count' => 0,
                                        'user_message' => $purchase_dec['data']['user_message'] ?? $purchase_dec['message'],
                                        'admin_message' => $purchase_dec['data']['admin_message'] ?? $purchase_dec['message']
                                    ];
                        
                                }
                                logger('CABLE: '.json_encode($buy_electricity_subscription));

                                if($purchase_dec['status']){
                                    $success++;
                                    $status = 1;
                                    $wallet_before = User::where('id',$user_id)->first()->main_wallet;
                                    $wallet_after = $wallet_before - $amount;

                                }else{
                                    //it might be processing or it failed
                                    $status = -1;
                                    $failure++;
                                    $wallet_before = User::where('id',$user_id)->first()->main_wallet;
                                    $wallet_after = $wallet_before;
                                }
                                //simulate success

                                $user_message = $purchase_dec['data']['user_message'] ?? $purchase_dec['message'];
                                $admin_message = $purchase_dec['data']['admin_message'] ?? $purchase_dec['message'];
                                $display_results[$i] = array(
                                    'message' => $user_message,
                                    'admin_message' => $admin_message,
                                    'status' => $status
                                );
                               
                    
                    
                                //this should not run though because it has already been checked
                                if($wallet_after <= 0){
                                    $status = -1;
                                    $user_message = 'Failed due to insufficient balance';
                                    $admin_message = 'Failed due to insufficient balance';
                                    $failure++;
                                    $display_results[$i] = array(
                                        'message' => $user_message,
                                        'admin_message' => $admin_message,
                                        'status' => $status
                                    );
                                }

                                $description = 'Purchase of cable subscription';
                                $creationData['transaction_category'] = 'cable_subscription';
                                $creationData['user_id'] = $user_id;
                                $creationData['api_id'] = $plan_id;
                                $creationData['txn_reference'] = $txn_reference;
                                $creationData['wallet_category'] = $request->wallet_category;
                                $creationData['affiliate_product_plan_id'] = $request->cable_product_plan_id;
                                $creationData['phone_number'] =  NULL;
                                $creationData['smart_card_number'] = $request->smart_card_number;
                                $creationData['cable_tv_slots'] = 1;
                                $creationData['amount'] = $amount;
                                $creationData['discounted_amount'] = $amount;
                                $creationData['status'] = $status;
                                $creationData['balance_before'] = $wallet_before;
                                $creationData['balance_after'] = $wallet_after;
                                $creationData['description'] = $description;
                                $creationData['user_screen_message'] = $user_message;
                                $creationData['admin_screen_message'] = $admin_message;
                                $transaction = Transaction::create($creationData);

                                $walletLog['user_id'] = $user_id;
                                $walletLog['transaction_category'] = 'CABLE';
                                $walletLog['balance_before'] = $wallet_before;
                                $walletLog['balance_after'] = $wallet_after;
                                $walletLog['transaction_id'] = $transaction->id;
                                $walletLog['action_by'] = auth()->user()->id;
                                $walletLog['description'] = 'CABLE Purchase from main wallet with transaction_id';
                                $this->log_wallet_transactions($walletLog);
                    
                                User::where('id',$user_id)->update([
                                    'main_wallet' => $wallet_after
                                ]);
                    
                            }

                            DB::commit();
                    
                        
                              if($failure > 1){
                                return response()->json(['status'=>2, 'message'=>" $failure issue(s) found. Check transaction history", 'data' => $display_results  ]);   
                              }
                              
                              //refund
                              else if($status == 2){
                                  return response()->json([ 'status'=>2, 'message'=>$user_message, 'data' => $display_results  ]);   
                              }

                              else if($status == 1){
                                return response()->json(['status'=>1, 'message'=>"Transaction was successful", 'data' => $display_results  ]);   
                              }
  
                            
                             return response()->json(['status'=> -1, 'message'=>$user_message, 'data' => $display_results  ]);   
                    
                        } else{
                            return response()->json(['status'=>'-1', 'message'=>'Wrong wallet selection', 'data'=>[]]);
                        }



        }catch(Exception $exception){
            logger($exception->getMessage().' on line: '. $exception->getLine());
            DB::rollBack();
            return response()->json(['status'=>'-1', 'message'=>'Something went wrong... Please try again', 'data'=>[]]);
        }

      
       
        

    }

    


    /**
     * Get all the products plans categories.
     */
    public function fetch_product_plan_categories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'network_id' => 'required',
        ]);
          
        if ($validator->stopOnFirstFailure()->fails()) {
            return response()->json(['status'=>'-1', 'message'=>'network is required','data' => $request->all() ]);
        }

        $network = $request->network_id;
        $product_id = Product::where('slug','cable_subscription')->first()->id;
        $product_plans_categories = ProductPlanCategory::whereIn('product_id',$product_id)
        ->where('visibility',1)
        ->where('network_id',$network)->get();
        
        return response()->json(['status'=>'1', 'message'=>'Product plans categories fetched','data' => $product_plans_categories ]);

    }

     /**
     * Get all the products plans. NOT IN USE FOR NOW
     */
    // public function fetch_product_plans(Request $request)
    // {
    //     $network_id = $request->network_id ?? '';
    //     $plan_category_id = $request->plan_category_id ?? '';
    //     $product_id = Product::where('slug','cable_subscription')->first()->id;



    //     if($plan_category_id == ''){
    //         $product_plan_categories = ProductPlanCategory::select('id','automation_id')->where('product_id',$product_id)->where('network_id',$network_id)->get();
    //     }else{
    //         $product_plan_categories = ProductPlanCategory::select('id','automation_id')
    //         ->where('product_id',$product_id)
    //         ->where('network_id',$network_id)
    //         ->where('id',$plan_category_id)
    //         ->get();
    //     }


       
    //     $product_planss = [];
    //     $counter =0;

    //    //TODO: 
    //     $user_details = auth()->user();
    //     $user_plan_id = $user_details->user_plan_id;
    //     $user_id = $user_details->id;
    //     $user_level = UserPlan::select('plan_level')->where('id',$user_plan_id)->first();
    //     $plan_level = $user_level->plan_level;


    //     foreach($product_plan_categories as $key=>$product_plan_category){
    //         //get the automation id
    //         //get the product_category_id 
    //         $product_plans = ProductPlan::where('product_plan_category_id',$product_plan_category->id)
    //         ->where('automation_id',$product_plan_category->automation_id)
    //         ->get();
    //         if(count($product_plans) > 0){
    //             foreach($product_plans as $product_plan){

    //                 $user_level_selling = "user_level_".$plan_level."_selling_price";
    //                 // $user_level_selling = "{user_level_$user_level_selling_price}";
    //                 $selling_price = $product_plan->$user_level_selling;
    //                 if($product_plan){
    //                     $counter++;
    //                     $product_planss[$counter]['product_plan_id'] = $product_plan->id;
    //                     $product_planss[$counter]['selling_price'] = $selling_price;
    //                     $product_planss[$counter]['product_plan_name'] = $product_plan->product_plan_name;
    //                     $product_planss[$counter]['data_size_in_mb'] = $product_plan->data_size_in_mb;
    //                     $product_planss[$counter]['validity_in_days'] = $product_plan->validity_in_days;    
    //                     $product_planss[$counter]['automation_id'] = $product_plan->automation_id;    
    //                 }
    //             }
    //         }    
    //     }
        
           
    //     return response()->json(['status'=>'1','user_level'=>$plan_level ,'message'=>'Product plans fetched','counter' =>count($product_planss),'data' => $product_planss ]);
    //     // return response()->json(['status'=>'1','user_level'=>$user_plan_id ,'message'=>'Product plans fetched','counter' =>count($product_planss),'data' => $product_planss ]);

    // }

    


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
