<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\ProductPlan;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\ProductPlanCategory;

class ParentSyncController extends Controller
{
    public function syncplans(Request $request){
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://oresamsub.com/api/v1/user/syncplans',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: 01a472d9582fc1eb9b22cc2f48badf2eb8c0573f',
            'Content-Type: application/json',
            'Accept: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response_dec = json_decode($response,true);

        // return $response;

        //networks and products wont change on the parent for now...

        //plan categories
        foreach($response_dec['data']['product_plan_categories'] as $pp_category ){
         
            $networkapiid = $pp_category['network']['api_id'] ?? NULL;
            if(! $networkapiid ){
                $networkid = NULL;
            }else{
                $networkdet = Network::where('api_id',$networkapiid)->first();
                $networkid = (int) $networkdet->id;
            }

            $ppexist = ProductPlanCategory::where('api_id',$pp_category['api_id'])->first();
            if($ppexist){
                //just update the network and product.   
                $ppexist->update([
                    'product_plan_category_name' => $pp_category['product_plan_category_name'],
                    'product_id' => (int) $pp_category['product']['api_id'],
                    'network_id' => $networkid
                ]);

            }else{

                ProductPlanCategory::create([
                    'api_id' =>$pp_category['api_id'],
                    'product_plan_category_name' => $pp_category['product_plan_category_name'],
                    'product_id' => (int) $pp_category['product']['api_id'],
                    'network_id' => $networkid,
                    'is_hot_sales' => $pp_category['is_hot_sales'] ? 1 : 0,
                ]);

            }

        }


        //plans
        foreach($response_dec['data']['plans'] as $res ){
            $api_id = $res['api_id'];
            // $product = $res['plan_category']['product'];
            // $network = $res['network'];
            // $plan_category = $res['plan_category'];
            $product_plan_name = $res['product_plan_name'];
            $data_size_in_mb = $res['data_size_in_mb'];
            $validity_in_days = $res['validity_in_days'];
            $cost_price_aff_1 = $res['cost_price_aff_1'];
            $cost_price_aff_2 = $res['cost_price_aff_2'];
            $cost_price_aff_3 = $res['cost_price_aff_3'];
            $cost_price_aff_4 = $res['cost_price_aff_4'];
            $cost_price_aff_5 = $res['cost_price_aff_5'];
            $cost_price_aff_6 = $res['cost_price_aff_6'];
            $cost_price_aff_7 = $res['cost_price_aff_7'];
            $cost_price_aff_8 = $res['cost_price_aff_8'];
            $cost_price_aff_9 = $res['cost_price_aff_9'];
            $cost_price_aff_10 = $res['cost_price_aff_10'];
            $cost_price_aff_11 = $res['cost_price_aff_11'];
            $cost_price_aff_12 = $res['cost_price_aff_12'];

            $pp_plan = ProductPlan::where('api_id',$api_id)->first();          
            if($api_id != ''){
                $plan_category_api_id = $res['plan_category']['api_id'] ?? 'nilll';
                $get_cat_id = ProductPlanCategory::where('api_id',$plan_category_api_id)->first();
                $categid = $get_cat_id->id; 
                $productid = $get_cat_id->product_id;
                logger('pid: '.$productid);


                if($productid == 2 || $productid == 3){
                    //airtime or elect
                    $profitcat = 'percent';
                    $priceprof = 1;
                }else{
                    $profitcat = 'flat';
                    $priceprof = 50;
                }

                if($get_cat_id){
                    if($pp_plan){
                        logger('opop');
                        //exists: just update
                        $pp_plan->update([
                            'product_plan_category_id' => $categid, //not sure how to add this yet. use the api id
                            'cost_price_1' => $cost_price_aff_1,
                            'cost_price_2' => $cost_price_aff_2,
                            'cost_price_3' => $cost_price_aff_3,
                            'cost_price_4' => $cost_price_aff_4,
                            'cost_price_5' => $cost_price_aff_5,
                            'cost_price_6' => $cost_price_aff_6,
                            'cost_price_7' => $cost_price_aff_7,
                            'cost_price_8' => $cost_price_aff_8,
                            'cost_price_9' => $cost_price_aff_9,
                            'cost_price_10' => $cost_price_aff_10,
                            'cost_price_11' => $cost_price_aff_11,
                            'cost_price_12' => $cost_price_aff_12,
                            'data_size_in_mb' => $data_size_in_mb,
                            'validity_in_days' => $validity_in_days,
                            'profit_category' => $profitcat
                        ]);

                    }else{

                        ProductPlan::create([
                            'api_id' => $api_id,
                            'product_plan_name' => $product_plan_name,
                            'product_plan_category_id' => $categid, //not sure how to add this yet. use the api id
                            'cost_price_1' => $cost_price_aff_1,
                            'cost_price_2' => $cost_price_aff_2,
                            'cost_price_3' => $cost_price_aff_3,
                            'cost_price_4' => $cost_price_aff_4,
                            'cost_price_5' => $cost_price_aff_5,
                            'cost_price_6' => $cost_price_aff_6,
                            'cost_price_7' => $cost_price_aff_7,
                            'cost_price_8' => $cost_price_aff_8,
                            'cost_price_9' => $cost_price_aff_9,
                            'cost_price_10' => $cost_price_aff_10,
                            'cost_price_11' => $cost_price_aff_11,
                            'cost_price_12' => $cost_price_aff_12,
                            'aff_level_1_max_profit' => $priceprof,
                            'aff_level_2_max_profit' => $priceprof,
                            'aff_level_3_max_profit' => $priceprof,
                            'aff_level_4_max_profit' => $priceprof,
                            'aff_level_5_max_profit' => $priceprof,
                            'aff_level_6_max_profit' => $priceprof,
                            'aff_level_7_max_profit' => $priceprof,
                            'aff_level_8_max_profit' => $priceprof,
                            'aff_level_9_max_profit' => $priceprof,
                            'aff_level_10_max_profit' => $priceprof,
                            'aff_level_11_max_profit' => $priceprof,
                            'aff_level_12_max_profit' => $priceprof,
                            'data_size_in_mb' => $data_size_in_mb,
                            'validity_in_days' => $validity_in_days,
                            'profit_category' => $profitcat
                        ]);

                    }
                }
                  
            }else{
                logger('Should not run');
            }
            
           
        }
        
        //  dd($response);
        return $response;
    }

    public function queryAirtimeTransaction(Request $request){

        $pending_transactions = Transaction::where('admin_screen_message','pending_airtime_transaction')
        ->whereNotNull('txn_reference')
        ->where('transaction_category','airtime')
        ->where('status',0) 
        ->get();

        foreach($pending_transactions as $pending_transaction){
                
                $transaction_ref = $pending_transaction->txn_reference;
                $wallet_category = $pending_transaction->wallet_category;
                $curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://oresamsub.com/api/v1/user/fetch_transaction?reference='.$transaction_ref,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: 01a472d9582fc1eb9b22cc2f48badf2eb8c0573f',
                    'Content-Type: application/json',
                    'Accept: application/json'
                ),
                ));
                $response = curl_exec($curl);
                $decoderes = json_decode($response,true);
                curl_close($curl);

                if( isset($decoderes['data']['status']) && $decoderes['data']['status'] == 1 ){
                    //its successful
                    $pending_transaction->update([
                        'status' => 1,
                        'user_screen_message' => $decoderes['data']['user_screen_message'] ?? 'Succesfully processed',
                        'admin_screen_message' => $decoderes['data']['admin_screen_message'] ?? 'Succesfully processed',
                        'set_for_manual' => 0, //might not be needed sef
                    ]);
                }else if(isset($decoderes['data']['status']) && ($decoderes['data']['status'] == 2 || $decoderes['data']['status'] == -1) ){
                    //either failed or refunded.

                    if($wallet_category == 'main_wallet'){
                        $user_id = $pending_transaction->user->id;
                        $amount_deducted =  $pending_transaction->discounted_amount ?? $pending_transaction->amount;
                        $former_wallet_balance =  $pending_transaction->user->main_wallet;
                        $new_wallet_balance = $pending_transaction->user->main_wallet + $amount_deducted;
                
                        //update user wallet
                         $pending_transaction->user->update([
                            'main_wallet' => $new_wallet_balance
                         ]); 
                
                         $userinfooo = auth()->user()->username.' '.auth()->user()->email;
                         $pending_transaction->update([
                            'status' => $decoderes['data']['status'],
                            'set_for_manual' => 0,
                            'balance_after' => $pending_transaction->balance_before,
                            'user_screen_message' => $decoderes['data']['user_screen_message'] ?? 'Transaction failed',
                            'admin_screen_message' => $decoderes['data']['admin_screen_message'] ?? 'Transaction failed',
                         ]); 
                
                         $walletLog['user_id'] = $user_id;
                         $walletLog['transaction_category'] = 'REFUND_TRANSACTION';
                         $walletLog['balance_before'] = $former_wallet_balance;
                         $walletLog['balance_after'] = $new_wallet_balance;
                         $walletLog['transaction_id'] = $pending_transaction->id;
                         $walletLog['action_by'] = auth()->user()->id;
                         $walletLog['description'] = 'Transaction was refunded for the ID: '. $pending_transaction->id;
                         $this->log_wallet_transactions($walletLog);
                        //log: refund
             
                
                    }else{
                        //dont treat for data_walllet for now
                        logger('data_wallet not available at the moment');
                    }

                    //also update that user balance
                }



                logger('Parent txn response: '.$response);
                sleep(1);
        }

        logger(count($pending_transactions). ' Pending airtime transactions processed @ '.date('Y-m-d H:i:s'));    
        echo 'Pending transactions processed @ '.date('Y-m-d H:i:s');    
        exit;
        
       
    }
}
