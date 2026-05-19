<?php

namespace App\Console\Commands;

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\Automation;
use App\Models\Network;
use App\Models\ProductPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Automation\AutomationLogic;
use App\Services\Automation\MegaSubPlugAutomation\MegaSubVendAirtime;
use App\Services\Automation\MsOrgGroupAutomation\MsOrgGroupAutomation;
use App\Services\Utils\UtilService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ProcessPendingAirtimeTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-pending-airtime-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending airtime transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
            //sync with parent.


            $pending_transactions = Transaction::withoutGlobalScope('affiliate')
            ->where('admin_screen_message','pending_airtime_transaction')
            ->whereNotNull('txn_reference')
            ->where('transaction_category','airtime')
            ->where('status',0) 
            ->get();
    
            foreach($pending_transactions as $pending_transaction){

                     $getAff = Affiliate::select('parent_key','id')->where('id',$pending_transaction->affiliate_id)->first();
                     $key = $getAff->parent_key;
                    
                    $transaction_ref = $pending_transaction->txn_reference;
                    $wallet_category = $pending_transaction->wallet_category;
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://oresamsub.com/api/v1/user/fetch_transaction?reference='.$transaction_ref, #this is the key
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: '.$key,
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
                    
                            //  $userinfooo = $pending_transaction->user->username.' '.auth()->user()->email;
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
                             $walletLog['action_by'] = $pending_transaction->user->id;
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
