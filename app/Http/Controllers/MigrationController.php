<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Network;
use App\Models\Product;
use App\Models\UserPlan;
use App\Models\ProductPlan;
use Illuminate\Http\Request;
use App\Models\FundingOption;
use App\Models\UserVirtualAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MigrationController extends Controller
{

    // public function migrate_to_key_product_tables(){
    //     $productsold = DB::table('productsold')->get();
    //     $networksold = DB::table('networksold')->get();
    //     $product_plansold = DB::table('product_plansold')->get();
    //     $product_plan_categoriessold = DB::table('product_plan_categoriessold')->get();


    //       foreach($product_plansold as $oldplan){
    //         $checkexist = ProductPlan::where('product_plan_name',$oldplan->product_plan_name)->first();
    //         if(! $checkexist){
    //             foreach($product_plan_categoriessold as $oldplancategory){
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //                 if($oldplancategory->id == ''){
    //                     $plancategory = 1;
    //                 }
    //             }
    //             ProductPlan::create([
    //                 'product_plan_name' => $oldplan->product_plan_name,
    //                 'product_plan_category_id' => 1,
    //                 'admin_cost_price' => $oldplan->cost_price,
    //                 'cost_price' => $oldplan->cost_price,
    //                 'api_id' => $oldplan->api_id,
    //                 'data_size_in_mb' => $oldplan->data_size_in_mb,
    //                 'validity_in_days' => $oldplan->validity_in_days,
    //                 'automation_product_plan_id' => $oldplan->automation_product_plan_id,
    //                 'automation_id' => $oldplan->automation_id
    //             ]);  
    //         }   
    //     }
    //     // $plancategoriesold = DB::table('product_plan_categories_old')->get();
    // }

    // public function migrate_product_plans(Request $request){
    //     // dd('shut it');

    //     $oldplans = DB::table('product_plansold2')->get();
    //     foreach($oldplans as $oldplan){

    //         // $checkexist = ProductPlan::where('product_plan_name',$oldplan->product_plan_name)
    //         // ->where('product_plan_category_id',)
    //         // ->first();
    //         // if(! $checkexist){
    //             if($oldplan->automation_id == '9c2887ea-55b5-4f19-904e-e490a10682ea'){
    //                 $automation_id = 1;
    //             }
    //             if($oldplan->automation_id == '9ed68ac0-5893-4101-be97-72dcf88335d2'){
    //                 $automation_id = 2;
    //             }
    //             if($oldplan->automation_id == '9f0b8cac-b637-47ed-8c97-d65512075690'){
    //                 $automation_id = 3;
    //             }
    //             if($oldplan->automation_id == '9f0f23aa-f613-43cd-9cec-882daa28c2ea'){
    //                 $automation_id = 4;
    //             }
    //             if($oldplan->automation_id == '9f1553ed-d7ca-4565-99d6-b12ffe9196f0'){
    //                 $automation_id = 5;
    //             }
    //             if($oldplan->automation_id == '9f470ffb-9bab-4be3-bd70-7614f70981cf'){
    //                 $automation_id = 6;
    //             }
    //             if($oldplan->automation_id == '9f83c37d-66c7-42b4-aa1e-404750f1aa4d'){
    //                 $automation_id = 7;
    //             }
    //             if($oldplan->automation_id == '9fa6447f-3e25-4a7e-8e90-e245ce0d5337'){
    //                 $automation_id = 8;
    //             }
    //             if($oldplan->automation_id == '9fb8371e-c6cb-4249-8957-be765a01c2c4'){
    //                 $automation_id = 9;
    //             }

                
                
    //             if($oldplan->product_plan_category_id == '9c39f216-0faf-4924-bee6-52a1149341ef'){
    //                 $ppcategory = 30;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-089c-44fc-a535-cc6f6a56bf68'){
    //                 $ppcategory = 18;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-0b12-4c61-b72a-f5ff38b0a689'){
    //                 $ppcategory = 22;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-0d65-4f57-a15f-db1b07c58c95'){
    //                 $ppcategory = 26;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-03e8-4417-bcc9-c098e77f2c51'){
    //                 $ppcategory = 10;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-063f-40ce-bba1-c0edb16e05a5'){
    //                 $ppcategory = 14;
    //             }
    //             if($oldplan->product_plan_category_id == '7559521a-272e-4b27-9330-c1442154626f'){
    //                 $ppcategory = 6;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-0f19-4ba0-96ee-decb9ed99a82'){
    //                 $ppcategory = 29;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-0805-4e5c-89b6-2c251f5821ab'){
    //                 $ppcategory = 17;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-0a81-4113-abf1-65e18c728ddc'){
    //                 $ppcategory = 21;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-0ccf-4860-aab5-60b25eab9e3a'){
    //                 $ppcategory = 25;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-02d9-4a46-b8de-eb48f668da88'){
    //                 $ppcategory = 9;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-05ab-4db8-8de8-fbe47374224a'){
    //                 $ppcategory = 13;
    //             }
    //             if($oldplan->product_plan_category_id == '93d019fc-d9a0-4bcd-957c-8a11a9e5e133'){
    //                 $ppcategory = 5;
    //             }
    //             if($oldplan->product_plan_category_id == 'a798c9a4-cd1b-4bd1-b26c-8932119d00a5'){
    //                 $ppcategory = 33;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-0e89-4455-8f37-c764c5f26ead'){
    //                 $ppcategory = 28;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-076e-4697-b93e-785e05643fa5'){
    //                 $ppcategory = 16;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-09f1-433d-ab74-f86737ea7f1e'){
    //                 $ppcategory = 20;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-0c42-48fc-af5a-528e86d1de12'){
    //                 $ppcategory = 24;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-020d-4d37-842b-840a7ff82d54'){
    //                 $ppcategory = 8;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-0513-4050-994d-59a60e99c464'){
    //                 $ppcategory = 12;
    //             }
    //             if($oldplan->product_plan_category_id == 'fb1064bd-0d51-4eb1-b78f-fa74fab4b89f'){
    //                 $ppcategory = 4;
    //             }
    //             if($oldplan->product_plan_category_id == '9ade7334-bfae-4fe1-9bc1-cd78fef6fac8'){
    //                 $ppcategory = 31;
    //             }
    //             if($oldplan->product_plan_category_id == 'e2d7b231-7c9f-44dd-9b05-7c27ed29e16e'){
    //                 $ppcategory = 1;
    //             }
    //             if($oldplan->product_plan_category_id == '1fb2806c-6dd5-49d3-badb-481d70e372a1'){
    //                 $ppcategory = 3;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-0df6-48d9-8530-3e320243058f'){
    //                 $ppcategory = 27;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-06d6-48fc-971e-d5778723497e'){
    //                 $ppcategory = 15;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-095b-46de-8466-88158a31e3e2'){
    //                 $ppcategory = 19;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-0bb9-472d-8775-6bc4379fec91'){
    //                 $ppcategory = 23;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-00a0-42ab-b195-558133f67a15'){
    //                 $ppcategory = 7;
    //             }
    //             if($oldplan->product_plan_category_id == '9c39f216-0484-4070-a83c-906999d62c97'){
    //                 $ppcategory = 11;
    //             }
    //             if($oldplan->product_plan_category_id == '0ed2d8b7-8c2e-4442-85c7-840f801552f0'){
    //                 $ppcategory = 2;
    //             }
    //             if($oldplan->product_plan_category_id == '12c6a955-8ce2-452e-ae09-40266fd6c531'){
    //                 $ppcategory = 34;
    //             }
    //             if($oldplan->product_plan_category_id == 'b3176d9f-6f12-45e0-9640-71c509271825'){
    //                 $ppcategory = 32;
    //             }

    //             ProductPlan::create([
    //                 'product_plan_name' => $oldplan->product_plan_name,
    //                 'product_plan_category_id' => $ppcategory,
    //                 'automation_product_plan_id' => $oldplan->automation_product_plan_id,
    //                 'automation_id' => $automation_id,
    //                 'admin_cost_price' => $oldplan->cost_price,
    //                 'cost_price' => $oldplan->cost_price,
    //                 'api_id' => $oldplan->api_id,
    //                 'data_size_in_mb' => $oldplan->data_size_in_mb,
    //                 'validity_in_days' => $oldplan->validity_in_days,
    //             ]);  
    //         // }
            
    //     }

    //     // dd('DONE...');
    // }

    //migrate users
    public function migrate_users(Request $request){
        dd('dont run');
        set_time_limit(0);
        $users_to_migrate = DB::table('members')
        ->where('migrated',0)
        ->limit(2000)
        ->get();
        // echo count($users_to_migrate);
         //  $user_migrate->username.'<br>';
         $role_details = Role::where('role_name','User')->first();
         $default_reseller_plan = UserPlan::where('is_default',1)->first();

         //FIRST TO MIGRATE USERS
        //  foreach($users_to_migrate as $user_migrate){
        //     $fullname = $user_migrate->name;
        //     $username = $user_migrate->username;
        //     $pin = $user_migrate->pin;
        //     $phone = $user_migrate->phone;
        //     $email = $user_migrate->email;
        //     $referby = $user_migrate->referby; //username
        //     $main_wallet_bal = $user_migrate->wallet;
        //     $old_platform_password = $user_migrate->password; //
        //     $checkduplicateentry = User::where(function($query) use ($username,$phone,$email){
        //         $query->where('username',$username)
        //               ->orWhere('phone_number',$phone)
        //               ->orWhere('email',$email);
        //     })->first();
        //     if($checkduplicateentry){
        //         //entry exists already
        //         echo $checkduplicateentry->username.' imported already <br>'; 
        //     }else if($user_migrate->phone == '' || $user_migrate->username == '' || $user_migrate->email == ''){
        //         echo $user_migrate->username.' is likely with some empty fields <br>'; 
        //         DB::table('members')->where('username',$user_migrate->username)->update(['migrated'=> -1]); //indicate issue

        //     } else{


        //         $fullname_arr = explode(' ',$fullname);
        //         if(count($fullname_arr) == 1){
        //             $new_first_name = $fullname_arr[0];
        //             $new_last_name = $fullname_arr[0];
        //         }else if(count($fullname_arr) > 1){
        //             $new_first_name = $fullname_arr[0];
        //             $new_last_name = $fullname_arr[1];
        //         }
               
        //         $data['first_name'] = $new_first_name;
        //         $data['last_name'] = $new_last_name;
        //         // $data['other_names'] = $request->other_names;
        //         $data['pin'] = $pin;
        //         $data['phone_number'] = $phone;
        //         $data['email'] = $email;
        //         $data['main_wallet'] = $main_wallet_bal;
        //         $data['username'] = $username;
        //         $data['upline_id'] = NULL;
        //         // $data['upline_id'] = $upline_id;
        //         $data['role_id'] = $role_details->id;
        //         $data['user_plan_id'] = $default_reseller_plan->id;
        //         $data['password'] = Hash::make('password'); //defaulted to just password
        //         $data['old_platform_password'] = $old_platform_password; //old platform password
        //         $data['email_verified_at'] = date('Y-m-d H:i:s');
        //         $user = User::create($data);

        //         DB::table('members')->where('username',$username)->update(['migrated'=> 1]);
        //         echo $username.' successfully migrated <br>'; 

        //     }
        // }

   
        //SECOND TO MIGRATE REFERR.
        // check if that referby is not null or empty and it has a record
        //  $getRowsWithUplines = DB::table('members')->get();
        //  if( count($getRowsWithUplines) > 0 ){
        //     foreach($getRowsWithUplines as $each_member){

        //         $referby = $each_member->referby;
        //         $email = $each_member->email;

        //         if($referby != '' && $referby != NULL && $referby != 0){
        //             $useruplinecheck = User::where('username',$referby)->first();
        //             if($useruplinecheck){
        //                 //update here
        //                 $userdownline_to_update = User::where('email',$email)->update([
        //                     'upline_id' => $useruplinecheck->id
        //                 ]);
        //                 echo 'Record:  '.$referby.' ===> '.$email.'<br>';

        //             }
        //         }
               
        //     }
        //  }else{
        //         echo 'No record found';
        //  }


        //THIRD TO MIGRATE REFERR.
        //MIGRATE JUST balances
        //  $getRowsWithUplines = DB::table('members')->get();
        //  if( count($getRowsWithUplines) > 0 ){
        //     foreach($getRowsWithUplines as $each_member){

        //         $wallet_balance = $each_member->wallet;
        //         $referby = $each_member->referby;
        //         $email = $each_member->email;

        //         User::where('email',$email)->update([
        //             'main_wallet' => $wallet_balance
        //         ]);
        //         echo 'Record:  '.$email.' => '.$wallet_balance.'<br>';

        //     }
        //  }else{
        //         echo 'No record found';
        //  }

       
    }

    //migrate virtual accounts
    public function migrate_accounts(){
        dd('dont run');
        set_time_limit(0);
        $bank_accounts = DB::table('members_bank_account')
        ->where('migrated',0)
        // ->limit(10)
        ->get();
        foreach($bank_accounts as $bankacct){
            // echo $bankacct->userid.'<br>';
            $userid = $bankacct->userid;
            $accname = $bankacct->accname;
            $accno = $bankacct->accno;
            $bankname = $bankacct->bankname;
            $bank_id = $bankacct->bank_id;

            //check
            // $table->foreignUuid('funding_option_id')->constrained('funding_options');
            // $table->foreignUuid('user_id')->constrained('users');
            // $table->string('funding_slug')->nullable();
            // $table->string('response_status')->nullable();
            // $table->string('bank_name')->nullable();
            // $table->string('bank_code')->nullable();
            // $table->string('account_name')->nullable();
            // $table->string('account_email')->nullable();
            // $table->string('account_number')->nullable();
            // $table->string('account_reference')->nullable();
            // $table->string('bvn')->nullable();
            $funding_option = FundingOption::where('slug','crystal_pay')->first();
            $id = $funding_option->id;
            $slug = $funding_option->slug;

            $useridold = $bankacct->userid;

            $check_user_id = DB::table('members')->where([
                'id' => $useridold
            ])->first();
            if($check_user_id){
                //means the user exists
                $username = $check_user_id->username;

                //now check if exists on Users table
                $checkonusertbl = User::where('username',$username)->first();
                if($checkonusertbl){
                    //get the userid
                    $useriddd = $checkonusertbl->id;

                    //only at this point can you create
                    if($bank_id == 1 || $bank_id == 3 || $bank_id == 7 || $bank_id == 6){
                    // if($bank_id == 6){

                        $checkva = UserVirtualAccount::where('user_id',$useriddd)->where('bank_code',$bank_id)->first();
                        if(! $checkva ){

                            $datacreate['funding_option_id'] = $id;
                            $datacreate['user_id'] = $useriddd;
                            $datacreate['account_name'] = $accname;
                            $datacreate['bank_code'] = $bank_id;
                            $datacreate['bank_name'] = $bankname;
                            $datacreate['account_number'] = $accno;
                            $datacreate['funding_slug'] = 'crystal_pay';
                            $datacreate['response_status'] = 'Success';
            
                            //just add only wema banks
                            UserVirtualAccount::create($datacreate);
    
                            //update migration
                            DB::table('members_bank_account')
                            ->where('userid',$useridold)
                            ->update(['migrated'=>1]);
                            
                        }
                    
        
                    }
                }else{
                 echo "$useridold record not found on users table <br>";
                }
            }else{
                echo "$useridold record not found on members table <br>";
            }
        }
    }
}
