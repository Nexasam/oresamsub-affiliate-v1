<?php

namespace Database\Seeders;

use App\Models\Affiliate;
use App\Models\Role;
use App\Models\User;
use App\Models\Network;
use App\Models\Product;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\UserPlan;
use App\Models\Automation;
use App\Models\Permission;
use App\Models\ProductPlan;
use App\Models\Transaction;
use App\Models\FundingOption;
use App\Models\ProductCategory;
use App\Models\ReferralSetting;
use App\Models\UserProductPlan;
use Illuminate\Database\Seeder;
use App\Models\UserBulkDataWallet;
use App\Models\LandingPagesSetting;
use App\Models\ProductPlanCategory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {


        //create the default list here
        // ReferralSetting::create();

        // affiliate seeds (slug = used in affiliate_id columns)
        $affiliateSeeds = [
            [
                'name' => 'Oresams',
                'slug' => 'oresams',
                'address' => 'Oresams HQ',
                'ip_address' => '127.0.0.20',
                'domain_url' => 'emiplug.nameee.ng',
                'contact_phone' => '08000000021',
                'contact_email' => 'hi@oresams.example2.com',
                'parent_email' => 'adebsholey4real222@gmail.com',
                'parent_key' => 'adebs12312qewuoqweiq2',
                'parent_plan_level' => 1,
                'activation_status' => 1,
            ],
            [
                'name' => 'Emmysub',
                'slug' => 'emmysub',
                'address' => 'Emmysub HQ',
                'ip_address' => '127.0.0.2',
                'domain_url' => 'emmysub.example.com',
                'contact_phone' => '08000000002',
                'contact_email' => 'hi@emmysub.example.com',
                'parent_email' => 'emmysub@gmail.com',
                'parent_key' => 'adeemmmy12312qewuoqweiq2',
                'parent_plan_level' => 1,
                'activation_status' => 1,
            ],
            [
                'name' => 'Estersub',
                'slug' => 'estersub',
                'address' => 'Estersub HQ',
                'ip_address' => '127.0.0.3',
                'domain_url' => 'estersub.example.com',
                'contact_phone' => '08000000003',
                'contact_email' => 'hi@estersub.example.com',
                'parent_email' => 'estersub@gmail.com',
                'parent_key' => 'estersub12312qewuoqweiq2',
                'parent_plan_level' => 1,
                'activation_status' => 1,
            ],
        ];

        //create landing pages defaultss
        $landing_pages_arr = config('landing_pages');
        foreach($landing_pages_arr as $key=>$value){
            $data['affiliate_id'] =1;
            $data['field_name'] = $key;
            $data['field_details'] = $value[2];
            $data['template_type'] = 'template_1';
            $data['visibility'] = 1;
            LandingPagesSetting::create($data);
        }

        $landing_pages2_arr = config('landing_template2_pages');
        foreach($landing_pages2_arr as $key2=>$value2){
            $data['affiliate_id'] =1;
            $data['field_name'] = $key2;
            $data['field_details'] = $value2[2];
            $data['template_type'] = 'template_2';
            $data['visibility'] = 1;
            LandingPagesSetting::create($data);
        }

        // FUNDING OPTIONS
        $xixapay = FundingOption::create([
            "funding_option_name" => "Xixapay",
            "slug" => "xixapay",
            "bank_name" => "xixapay",
            "biz_bvn" => "22228889922",
            "is_current_option" => "1",
            "api_public_key" => 'xxxxxxxx',
            "api_secret_key" =>'xxxxxxxx',
            "activation_status" =>1,
        ]);

        $monnify = FundingOption::create([
            "funding_option_name" => "Monnify",
            "slug" => "monnify",
            "bank_name" => "xixapay",
            "biz_bvn" => "22118772288",
            "contract_code" => "xxxxxxxxxx",
            "is_current_option" => "0",
            "api_public_key" => 'xxxxxxxx',
            "api_secret_key" =>'xxxxxxxx',
            "activation_status" =>0,    
        ]);


        DB::table('automations')->insert([
            [
                'id' => 1,
                'automation_name' => 'MEGASUBPLUG',
                'bank_name' => 'WEMA',
                'bank_accounts' => '7179164321',
                'electricity_url' => null,
                'cable_url' => null,
                'airtime_url' => null,
                'data_url' => null,
                'automation_group' => 'nil',
                'domain_url' => 'https://megasubplug.com/Login/',
                'whatsapp_support_link' => null,
                'slug' => 'megasubplug',
                'api_secret_key' => null,
                'api_public_key' => '148350810866d20735990ad',
                'api_password' => 'Pass4adebunmi%%',
                'activation_status' => '1',
                'created_at' => '2024-08-30 17:46:28',
                'updated_at' => '2025-05-04 18:43:49',
            ],
            [
                'id' => 2,
                'automation_name' => 'Affatech',
                'bank_name' => 'Palmpay',
                'bank_accounts' => '6648757245',
                'electricity_url' => 'https://www.affatech.com.ng/api/electricity',
                'cable_url' => 'https://www.affatech.com.ng/api/cable/',
                'airtime_url' => 'https://www.affatech.com.ng/api/topup/',
                'data_url' => 'https://www.affatech.com.ng/api/data/',
                'automation_group' => 'msorg',
                'domain_url' => 'https://www.affatech.com.ng/login',
                'whatsapp_support_link' => 'https://whatsapp.com',
                'slug' => 'affatech',
                'api_secret_key' => null,
                'api_public_key' => '4eec6e137f20284e43ce4df259c947367938ddc3',
                'api_password' => null,
                'activation_status' => '1',
                'created_at' => '2025-05-05 11:01:20',
                'updated_at' => '2025-05-05 11:01:20',
            ],
            [
                'id' => 3,
                'automation_name' => 'GongozConcept',
                'bank_name' => 'Palmpay',
                'bank_accounts' => '6624525459',
                'electricity_url' => 'www.gongozconcept.com/api/electricity',
                'cable_url' => 'www.gongozconcept.com/api/cable',
                'airtime_url' => 'https://www.gongozconcept.com/api/topup/',
                'data_url' => 'https://www.gongozconcept.com/api/data/',
                'automation_group' => 'msorg',
                'domain_url' => 'https://gongozconcept.com/login',
                'whatsapp_support_link' => 'https://api.whatsapp.com/send/?phone=2348056665655&text&type=phone_number&app_absent=0',
                'slug' => 'gongozconcept',
                'api_secret_key' => null,
                'api_public_key' => 'b0c6a134f312274083cf68c3c0cf218454ff66a8',
                'api_password' => null,
                'activation_status' => '1',
                'created_at' => '2025-05-31 19:25:35',
                'updated_at' => '2025-05-31 19:25:35',
            ],
            [
                'id' => 4,
                'automation_name' => 'DirectCoupon',
                'bank_name' => 'Palmpay',
                'bank_accounts' => '6606248182',
                'electricity_url' => 'https://directcoupon.com.ng/api/loadElectricity',
                'cable_url' => 'https://directcoupon.com.ng/api/loadCable',
                'airtime_url' => 'https://directcoupon.com.ng/api/loadAirtime',
                'data_url' => 'https://directcoupon.com.ng/api/loadCoupon',
                'automation_group' => 'nil',
                'domain_url' => 'https://directcoupon.com.ng/login',
                'whatsapp_support_link' => 'https://api.whatsapp.com/send/?phone=2347070790999&text&type=phone_number&app_absent=0',
                'slug' => 'directcoupon',
                'api_secret_key' => null,
                'api_public_key' => 'ad387c961441cc46b7e415c8d4e6bca64cfea57479db08ecb580ad05e9b367d3',
                'api_password' => null,
                'activation_status' => '1',
                'created_at' => '2025-06-02 14:15:17',
                'updated_at' => '2025-06-02 14:15:17',
            ],
            [
                'id' => 5,
                'automation_name' => 'Dancity',
                'bank_name' => 'Palmpay',
                'bank_accounts' => '6667944860',
                'electricity_url' => 'https://www.dancitysub.com/api/electricity/',
                'cable_url' => 'https://www.dancitysub.com/api/cable/',
                'airtime_url' => 'https://www.dancitysub.com/api/airtime/',
                'data_url' => 'https://www.dancitysub.com/api/data/',
                'automation_group' => 'msorg',
                'domain_url' => 'https://www.dancitysub.com/login',
                'whatsapp_support_link' => 'https://whatsapp.com',
                'slug' => 'dancity',
                'api_secret_key' => null,
                'api_public_key' => '1afdd3913981bc8268dc9c8c3027cecb3327367f',
                'api_password' => null,
                'activation_status' => '1',
                'created_at' => '2025-06-05 16:05:12',
                'updated_at' => '2025-06-05 16:05:12',
            ],
            [
                'id' => 6,
                'automation_name' => 'Paultechs',
                'bank_name' => 'Palmpay',
                'bank_accounts' => '6621555512',
                'electricity_url' => 'https://paultechs.com/api/electricity/',
                'cable_url' => 'https://paultechs.com/api/cable/',
                'airtime_url' => 'https://paultechs.com/api/airtime/',
                'data_url' => 'https://paultechs.com/api/data/',
                'automation_group' => 'nil',
                'domain_url' => 'https://paultechs.com/login',
                'whatsapp_support_link' => 'https://api.whatsapp.com/send/?phone=2347030989671',
                'slug' => 'paultechs',
                'api_secret_key' => 'a92847c2ebecc7fda7ee65e535447734e8f0f513',
                'api_public_key' => 'a92847c2ebecc7fda7ee65e535447734e8f0f513',
                'api_password' => null,
                'activation_status' => '1',
                'created_at' => '2025-06-30 10:26:36',
                'updated_at' => '2025-06-30 10:26:36',
            ],
            [
                'id' => 7,
                'automation_name' => 'samicsub',
                'bank_name' => 'Moniepoint',
                'bank_accounts' => '6402723263',
                'electricity_url' => 'sdf',
                'cable_url' => 'sdf',
                'airtime_url' => 'sdf',
                'data_url' => 'testll',
                'automation_group' => 'nil',
                'domain_url' => 'https://samicsub.com/user/login',
                'whatsapp_support_link' => 'sdf',
                'slug' => 'samicsub',
                'api_secret_key' => 'sdf',
                'api_public_key' => 'agaa',
                'api_password' => 'sdfjk',
                'activation_status' => '1',
                'created_at' => '2025-07-30 14:38:13',
                'updated_at' => '2025-07-30 14:38:13',
            ],
            [
                'id' => 8,
                'automation_name' => '9javtu',
                'bank_name' => 'Moniepoint',
                'bank_accounts' => '6243038108',
                'electricity_url' => 'https://9javtu.ng/API',
                'cable_url' => 'https://9javtu.ng/API',
                'airtime_url' => 'https://9javtu.ng/API',
                'data_url' => 'https://9javtu.ng/API',
                'automation_group' => 'nil',
                'domain_url' => 'https://9javtu.ng/login',
                'whatsapp_support_link' => 'whatsapp.com',
                'slug' => '9javtu',
                'api_secret_key' => '1838602068689dbc3d8207b',
                'api_public_key' => '1838602068689dbc3d8207b',
                'api_password' => 'Pass4adebunmi%%',
                'activation_status' => '1',
                'created_at' => '2025-08-16 18:17:05',
                'updated_at' => '2025-08-16 18:17:05',
            ],
            [
                'id' => 9,
                'automation_name' => 'Smeplug',
                'bank_name' => 'Rubies/Highstreet MFB',
                'bank_accounts' => '8880045627',
                'electricity_url' => 'https://smeplug.ng/api/v1/electricty/purchase',
                'cable_url' => 'https://smeplug.ng/api/v1/cable/purchase',
                'airtime_url' => 'https://smeplug.ng/api/v1/airtime/purchase',
                'data_url' => 'https://smeplug.ng/api/v1/data/purchase',
                'automation_group' => 'nil',
                'domain_url' => 'https://smeplug.ng',
                'whatsapp_support_link' => 'whatsapp.com',
                'slug' => 'smeplug',
                'api_secret_key' => '1564d3c87fc7f9ba45de0dd7ad9041ca4f86f1a0fffecd9e7476c6963564ea66',
                'api_public_key' => '1564d3c87fc7f9ba45de0dd7ad9041ca4f86f1a0fffecd9e7476c6963564ea66',
                'api_password' => null,
                'activation_status' => '1',
                'created_at' => '2025-08-25 16:24:35',
                'updated_at' => '2025-08-25 16:24:35',
            ],
            [
                'id' => 10,
                'automation_name' => 'Oresamsub',
                'bank_name' => 'Oresamsub',
                'bank_accounts' => '8880045627',
                'electricity_url' => 'https://oresamsub.com/api/v1/electricty/purchase',
                'cable_url' => 'https://oresamsub.com/api/v1/cable/purchase',
                'airtime_url' => 'https://oresamsub.com/api/v1/airtime/purchase',
                'data_url' => 'https://oresamsub.com/api/v1/data/purchase',
                'automation_group' => 'nil',
                'domain_url' => 'https://oresamsub.com',
                'whatsapp_support_link' => 'whatsapp.com',
                'slug' => 'oresamsub',
                'api_secret_key' => '1564d3c87fc7f9ba45de0dd7ad9041ca4f86f1a0fffecd9e7476c6963564ea66',
                'api_public_key' => '1564d3c87fc7f9ba45de0dd7ad9041ca4f86f1a0fffecd9e7476c6963564ea66',
                'api_password' => null,
                'activation_status' => '1',
                'created_at' => '2025-08-25 16:24:35',
                'updated_at' => '2025-08-25 16:24:35',
            ],
        ]);



        //NETWORKS
        $mtn_network = Network::create([
            'id' => 1,
            'api_id' => 1,
            'network_name' => 'MTN'
        ]);

        $glo_network = Network::create([
            'id' => 2,
            'api_id' => 4,
            'network_name' => 'GLO'
        ]);

        $airtel_network = Network::create([
            'id' => 3,
            'api_id' => 2,
            'network_name' => 'AIRTEL'
        ]);

        $_9mobile_network = Network::create([
            'id' => 4,
            'api_id' => 3,
            'network_name' => '9MOBILE'
        ]);

           
        
        //USER PLANS -     
        // $user_product_plans_percentage_for_basic_user_plan = 0;
        // $user_product_plans_percentage_for_gold_user_plan = 18;
        // $user_product_plans_percentage_for_diamond_user_plan = 24;
        // $user_product_plans_percentage_for_platinum_user_plan = 28;
        // Define six meaningful plans

            // $user_plan_basic = UserPlan::create([
            //     'user_plan_name' => 'Basic Plan',
            //     'plan_level' => 1,
            //     'updated_user_plan_name' => NULL,
            //     'is_default' => 1,
            // ]);

            // $user_plan_bronze = UserPlan::create([
            //     'user_plan_name' => 'Bronze Reseller Plan',
            //     'plan_level' => 2,
            //     'updated_user_plan_name' => NULL,
            //     'is_default' => 0,
            // ]);

            // $user_plan_gold = UserPlan::create([
            //     'user_plan_name' => 'Gold Reseller Plan',
            //     'plan_level' => 3,
            //     'updated_user_plan_name' => NULL,
            //     'is_default' => 0,
            // ]);

            // $user_plan_diamond = UserPlan::create([
            //     'user_plan_name' => 'Diamond Reseller Plan',
            //     'plan_level' => 4,
            //     'updated_user_plan_name' => NULL,
            //     'is_default' => 0,
            // ]);

            // $user_plan_platinum = UserPlan::create([
            //     'user_plan_name' => 'Platinum Reseller Plan',
            //     'plan_level' => 5,
            //     'updated_user_plan_name' => NULL,
            //     'is_default' => 0,
            // ]);

            // $user_plan_elite = UserPlan::create([
            //     'user_plan_name' => 'Elite Reseller Plan',
            //     'plan_level' => 6,
            //     'updated_user_plan_name' => NULL,
            //     'is_default' => 0,
            // ]);

            $user_plan_basic = UserPlan::create([
                'user_plan_name' => 'Basic Plan',
                'api_id' => 1,
                'plan_level' => 1,
                'updated_user_plan_name' => NULL,
                'is_default' => 1,
            ]);
            
            $user_plan_bronze = UserPlan::create([
                'user_plan_name' => 'Bronze Reseller Plan',
                'api_id' => 2,
                'plan_level' => 2,
                'updated_user_plan_name' => NULL,
                'is_default' => 0,
            ]);
            
            $user_plan_silver = UserPlan::create([
                'user_plan_name' => 'Silver Reseller Plan',
                'api_id' => 3,
                'plan_level' => 3,
                'updated_user_plan_name' => NULL,
                'is_default' => 0,
            ]);
            
            $user_plan_gold = UserPlan::create([
                'user_plan_name' => 'Gold Reseller Plan',
                'api_id' => 4,
                'plan_level' => 4,
                'updated_user_plan_name' => NULL,
                'is_default' => 0,
            ]);
            
            $user_plan_diamond = UserPlan::create([
                'user_plan_name' => 'Diamond Reseller Plan',
                'api_id' => 5,
                'plan_level' => 5,
                'updated_user_plan_name' => NULL,
                'is_default' => 0,
            ]);
            
            $user_plan_platinum = UserPlan::create([
                'user_plan_name' => 'Platinum Reseller Plan',
                'api_id' => 6,
                'plan_level' => 6,
                'updated_user_plan_name' => NULL,
                'is_default' => 0,
            ]);
            
            $user_plan_elite = UserPlan::create([
                'user_plan_name' => 'Elite Reseller Plan',
                'api_id' => 7,
                'plan_level' => 7,
                'updated_user_plan_name' => NULL,
                'is_default' => 0,
            ]);
            
            $user_plan_executive = UserPlan::create([
                'user_plan_name' => 'Executive Reseller Plan',
                'api_id' => 8,
                'plan_level' => 8,
                'updated_user_plan_name' => NULL,
                'is_default' => 0,
            ]);
            
            $user_plan_vip = UserPlan::create([
                'user_plan_name' => 'VIP Reseller Plan',
                'api_id' => 9,
                'plan_level' => 9,
                'updated_user_plan_name' => NULL,
                'is_default' => 0,
            ]);
            
            $user_plan_premium = UserPlan::create([
                'user_plan_name' => 'Premium Reseller Plan',
                'api_id' => 10,
                'plan_level' => 10,
                'updated_user_plan_name' => NULL,
                'is_default' => 0,
            ]);
            
            $user_plan_supreme = UserPlan::create([
                'user_plan_name' => 'Supreme Reseller Plan',
                'api_id' => 11,
                'plan_level' => 11,
                'updated_user_plan_name' => NULL,
                'is_default' => 0,
            ]);
            
            $user_plan_ultimate = UserPlan::create([
                'user_plan_name' => 'Ultimate Reseller Plan',
                'api_id' => 12,
                'plan_level' => 12,
                'updated_user_plan_name' => NULL,
                'is_default' => 0,
            ]);
            
           $globalUserPlans = DB::table('user_plans')->get();

           $affiliate1 = Affiliate::create([
            'name' => 'Oresams',
            'slug' => 'oresamss',
            'parent_email' => 'adebsholey4real@gmail.com',
            'parent_key' => 'adebsssb12312qewuoqweiq2',
            'parent_plan_level' => 1,
            'address' => 'Oresams HQ',
            'ip_address' => '127.0.0.1',
            'domain_url' => 'emiplug.name.ng',
            'contact_phone' => '08000000001',
            'contact_email' => 'adebsholey4real1@gmail.com',
            'activation_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
          ]);
          $affiliateId1 = $affiliate1->id;


               //
            // 1) Affiliate Plans (from user_plans)
            //
            foreach ($globalUserPlans as $plan) {
                $exists = DB::table('affiliate_user_plans')
                    ->where('affiliate_id', $affiliateId1)
                    ->where('user_plan_name', $plan->user_plan_name)
                    ->first();

                if (!$exists) {
                    DB::table('affiliate_user_plans')->insert([
                        'affiliate_id' => $affiliateId1,
                        'user_plan_name' => $plan->user_plan_name,
                        'updated_user_plan_name' => $plan->updated_user_plan_name ?? null,
                        'plan_level' => $plan->plan_level ?? null,
                        'is_default' => $plan->is_default ?? 0,
                        'visibility' => $plan->visibility ?? 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }


              

        //Roles
        $admin_role = Role::create([
            'role_name' => 'Admin',
        ]);
     
     
        //assign all permissions to Admin:
        // $file_permissions = config('permissions');
        // // logger($file_permissions);
        // foreach($file_permissions as $key=>$permission){
        //     if($file_permissions[$key]['slug'] != 'data_purchase'){
        //         Permission::updateOrCreate(
        //         [
        //             'role_id' => $admin_role->id,
        //             'permission_slug' => $file_permissions[$key]['slug'],
        //         ],
        //         [
        //             'permission_name' => $file_permissions[$key]['name'],
        //             'permission_slug' => $file_permissions[$key]['slug'],
        //             'permission_create' => $file_permissions[$key]['create'],
        //             'permission_read' => $file_permissions[$key]['read'],
        //             'permission_update' => $file_permissions[$key]['update'],
        //             'permission_delete' => $file_permissions[$key]['delete'],
        //         ]);
        //     }
        // }

        // $user_permissions = ['data_purchase'];
        // foreach($file_permissions as $key=>$permission){

        //     if( in_array($file_permissions[$key]['slug'],$user_permissions) ){
        //         Permission::updateOrCreate(
        //             [
        //                 'role_id' => $user_role->id,
        //                 'permission_slug' => $file_permissions[$key]['slug'],
        //             ],
        //             [
        //                 'permission_name' => $file_permissions[$key]['name'],
        //                 'permission_slug' => $file_permissions[$key]['slug'],
        //                 'permission_create' => $file_permissions[$key]['create'],
        //                 'permission_read' => $file_permissions[$key]['read'],
        //                 'permission_update' => $file_permissions[$key]['update'],
        //                 'permission_delete' => $file_permissions[$key]['delete'],
        //         ]);
        //     }
        // }
        //PERMISSION LATER...



         $user_role = Role::create([
            'role_name' => 'User',
        ]);
        
        //USERS
        User::factory()->create([
            'affiliate_id' => 1,
            'username' => 'samuel'.rand(11,99),
            'first_name' => 'Samuel',
            'last_name' => 'Adebunmi',
            'pin' => 1111,
            'main_wallet' => 20000,
            'role_id' => $admin_role,
            'user_plan_id' => 4,
            'email' => 'adebsholey4real@gmail.com',
            'phone_number' => '08168509044',
            'password' => Hash::make('password'),
        ]); 

       $user_ore =  User::factory()->create([
            'affiliate_id' => 1,
            'username' => 'oreofe'.rand(11,99),
            'first_name' => 'Oreofe',
            'last_name' => 'Adebunmi',
            'main_wallet' => 10000,
            'pin' => 1111,
            'role_id' => $user_role,
            'user_plan_id' => 1,
            'email' => 'oreofe@gmail.com',
            'phone_number' => '08198092334',
            'password' => Hash::make('password'),
        ]); 
        User::factory()->create([
            'affiliate_id' => 1,
            'username' => 'emmanuel'.rand(11,99),
            'first_name' => 'Emmanuel',
            'last_name' => 'Adebunmi',
            'main_wallet' => 15000,
            'pin' => 1111,
            'role_id' => $user_role,
            'user_plan_id' => 4,
            'email' => 'emmanuel@gmail.com',
            'phone_number' => '08198092771',
            'password' => Hash::make('password'),
        ]); 

        //PRODUCT CATEGORIES change to===> PRODUCTS
        $product_data = Product::create([
            'id' => 1,
            'api_id' => 1,
            'product_name' => 'DATA',
            'slug' => 'data',
            'visibility' => 1,
            'active_status' => 1
        ]);

        $product_airtime = Product::create([
            'id' => 2,
            'api_id' => 2,
            'product_name' => 'AIRTIME',
            'slug' => 'airtime',
            'visibility' => 1,
            'active_status' => 1
        ]);
        $product_bills = Product::create([
            'id' => 3,
            'api_id' => 3,
            'product_name' => 'UTILITY BILLS',
            'slug' => 'utility_bills',
            'visibility' => 1,
            'active_status' => 1
        ]);
        $product_cable = Product::create([
            'id' => 4,
            'api_id' => 4,
            'product_name' => 'CABLE SUBSCRIPTION',
            'slug' => 'cable_subscription',
            'visibility' => 1,
            'active_status' => 1
        ]);
        $product_epins = Product::create([
            'id' => 5,
            'api_id' => 5,
            'product_name' => 'E-PINS',
            'slug' => 'e_pins',
            'visibility' => 1,
            'active_status' => 1
        ]);
        $product_result_checker = Product::create([
            'id' => 6,
            'api_id' => 6,
            'product_name' => 'RESULT CHECKER',
            'slug' => 'result_checker',
            'visibility' => 1,
            'active_status' => 1
        ]); 




       // PRODUCT PLAN CATEGORIES - compulsory*** - for deeper classification
       $pr_plan_mtn_airtime_momo = ProductPlanCategory::create([
        'id' => 1,
        'api_id' => 33,
        'product_plan_category_name' => 'MTN AIRTIME (MOMO)',
        'product_id' => $product_airtime->id,
        'network_id' => $mtn_network->id,
        'is_hot_sales' => 0,
        // 'bulk_data_wallet_in_mb' => 1048576
      ]);

      $pr_plan_mtn_airtime_vtu = ProductPlanCategory::create([
        'id' => 2,
        'api_id' => 1,
        'product_plan_category_name' => 'MTN VTU (Virtual Top Up)',
        'product_id' => $product_airtime->id,
        'network_id' => $mtn_network->id,
        'is_hot_sales' => 0,
        // 'bulk_data_wallet_in_mb' => 1048576
      ]);

      $pr_plan_mtn_airtime_share_n_sell = ProductPlanCategory::create([
        'id' => 3,
        'api_id' => 3,
        'product_plan_category_name' => 'MTN AIRTIME (SHARE N SELL)',
        'product_id' => $product_airtime->id,
        'network_id' => $mtn_network->id,
        'is_hot_sales' => 0,
        // 'bulk_data_wallet_in_mb' => 1048576
      ]);


      $pr_plan_glo_airtime = ProductPlanCategory::create([
        'id' => 4,
        'api_id' => 34,
        'product_plan_category_name' => 'GLO VTU (Virtual Top Up)',
        'product_id' => $product_airtime->id,
        'network_id' => $glo_network->id,
        'is_hot_sales' => 0,
        // 'bulk_data_wallet_in_mb' => 1048576
      ]);
      

      $pr_plan_airtel_airtime = ProductPlanCategory::create([
        'id' => 5,
        'api_id' => 5,
        'product_plan_category_name' => 'AIRTEL VTU (Virtual Top Up)',
        'product_id' => $product_airtime->id,
        'network_id' => $airtel_network->id,
        'is_hot_sales' => 0,
        // 'bulk_data_wallet_in_mb' => 1048576
      ]);

      $pr_plan_9mobile_airtime = ProductPlanCategory::create([
        'id' => 6,
        'api_id' => 4,
        'product_plan_category_name' => '9MOBILE VTU (Virtual Top Up)',
        'product_id' => $product_airtime->id,
        'network_id' => $_9mobile_network->id,
        'is_hot_sales' => 0,
        // 'bulk_data_wallet_in_mb' => 1048576
      ]);
       

        // SME
        $pr_plan_mtn_sme_data = ProductPlanCategory::create([
            'id' => 7,
            'api_id' => 7,
            'product_plan_category_name' => 'MTN SME DATA',
            'product_id' => $product_data->id,
            'network_id' => $mtn_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);

        $pr_plan_glo_sme_data = ProductPlanCategory::create([
            'id' => 8,
            'api_id' => 8,
            'product_plan_category_name' => 'GLO SME DATA',
            'product_id' => $product_data->id,
            'network_id' => $glo_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);

        $pr_plan_airtel_sme_data = ProductPlanCategory::create([
            'id' => 9,
            'api_id' => 9,
            'product_plan_category_name' => 'AIRTEL SME DATA',
            'product_id' => $product_data->id,
            'network_id' => $airtel_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);

        $pr_plan_9mobile_sme_data = ProductPlanCategory::create([
            'id' => 10,
            'api_id' => 10,
            'product_plan_category_name' => '9MOBILE SME DATA',
            'product_id' => $product_data->id,
            'network_id' => $_9mobile_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);


         // SME2
         $pr_plan_mtn_sme2_data = ProductPlanCategory::create([
            'id' => 11,
            'api_id' => 11,
            'product_plan_category_name' => 'MTN SME2 DATA',
            'product_id' => $product_data->id,
            'network_id' => $mtn_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);

        $pr_plan_glo_sme2_data = ProductPlanCategory::create([
            'id' => 12,
            'api_id' => 12,
            'product_plan_category_name' => 'GLO SME2 DATA',
            'product_id' => $product_data->id,
            'network_id' => $glo_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);

        $pr_plan_airtel_sme2_data = ProductPlanCategory::create([
            'id' => 13,
            'api_id' => 13,
            'product_plan_category_name' => 'AIRTEL SME2 DATA',
            'product_id' => $product_data->id,
            'network_id' => $airtel_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);

        $pr_plan_9mobile_sme2_data = ProductPlanCategory::create([
            'id' => 14,
            'api_id' => 14,
            'product_plan_category_name' => '9MOBILE SME2 DATA',
            'product_id' => $product_data->id,
            'network_id' => $_9mobile_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576 
        ]);



         // CG
         $pr_plan_mtn_cg_data = ProductPlanCategory::create([
            'id' => 15,
            'api_id' => 15,
            'product_plan_category_name' => 'MTN CG DATA',
            'product_id' => $product_data->id,
            'network_id' => $mtn_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576 
        ]);

        $pr_plan_glo_cg_data = ProductPlanCategory::create([
            'id' => 16,
            'api_id' => 16,
            'product_plan_category_name' => 'GLO CG DATA',
            'product_id' => $product_data->id,
            'network_id' => $glo_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576 
        ]);

        $pr_plan_airtel_cg_data = ProductPlanCategory::create([
            'id' => 17,
            'api_id' => 17,
            'product_plan_category_name' => 'AIRTEL CG DATA',
            'product_id' => $product_data->id,
            'network_id' => $airtel_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576 
        ]);

        $pr_plan_9mobile_cg_data = ProductPlanCategory::create([
            'id' => 18,
            'api_id' => 18,
            'product_plan_category_name' => '9MOBILE CG DATA',
            'product_id' => $product_data->id,
            'network_id' => $_9mobile_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576 
        ]);


         // Gifting
         $pr_plan_mtn_gifting_data = ProductPlanCategory::create([
            'id' => 19,
            'api_id' => 19,
            'product_plan_category_name' => 'MTN GIFTING DATA',
            'product_id' => $product_data->id,
            'network_id' => $mtn_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576 
        ]);

        $pr_plan_glo_gifting_data = ProductPlanCategory::create([
            'id' => 20,
            'api_id' => 20,
            'product_plan_category_name' => 'GLO GIFTING DATA',
            'product_id' => $product_data->id,
            'network_id' => $glo_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576 
        ]);

        $pr_plan_airtel_gifting_data = ProductPlanCategory::create([
            'id' => 21,
            'api_id' => 21,
            'product_plan_category_name' => 'AIRTEL GIFTING DATA',
            'product_id' => $product_data->id,
            'network_id' => $airtel_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576 
        ]);

        $pr_plan_9mobile_gifting_data = ProductPlanCategory::create([
            'id' => 22,
            'api_id' => 22,
            'product_plan_category_name' => '9MOBILE GIFTING DATA',
            'product_id' => $product_data->id,
            'network_id' => $_9mobile_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576 
        ]);


        // share data
        $pr_plan_mtn_share_data = ProductPlanCategory::create([
            'id' => 23,
            'api_id' => 23,
            'product_plan_category_name' => 'MTN SHARE DATA',
            'product_id' => $product_data->id,
            'network_id' => $mtn_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576 
        ]);

        $pr_plan_glo_share_data = ProductPlanCategory::create([
            'id' => 24,
            'api_id' => 24,
            'product_plan_category_name' => 'GLO SHARE DATA',
            'product_id' => $product_data->id,
            'network_id' => $glo_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576 
        ]);

        $pr_plan_airtel_share_data = ProductPlanCategory::create([
            'id' => 25,
            'api_id' => 25,
            'product_plan_category_name' => 'AIRTEL SHARE DATA',
            'product_id' => $product_data->id,
            'network_id' => $airtel_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);

        $pr_plan_9mobile_share_data = ProductPlanCategory::create([
            'id' => 26,
            'api_id' => 26,
            'product_plan_category_name' => '9MOBILE SHARE DATA',
            'product_id' => $product_data->id,
            'network_id' => $_9mobile_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);


        // AWOOF data
        $pr_plan_mtn_awoof_data = ProductPlanCategory::create([
            'id' => 27,
            'api_id' => 27,
            'product_plan_category_name' => 'MTN AWOOF DATA',
            'product_id' => $product_data->id,
            'network_id' => $mtn_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);

        $pr_plan_glo_awoof_data = ProductPlanCategory::create([
            'id' => 28,
            'api_id' => 28,
            'product_plan_category_name' => 'GLO AWOOF DATA',
            'product_id' => $product_data->id,
            'network_id' => $glo_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);

        $pr_plan_airtel_awoof_data = ProductPlanCategory::create([
            'id' => 29,
            'api_id' => 29,
            'product_plan_category_name' => 'AIRTEL AWOOF DATA',
            'product_id' => $product_data->id,
            'network_id' => $airtel_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);

        $pr_plan_9mobile_awoof_data = ProductPlanCategory::create([
            'id' => 30,
            'api_id' => 30,
            'product_plan_category_name' => '9MOBILE AWOOF DATA',
            'product_id' => $product_data->id,
            'network_id' => $_9mobile_network->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);

        $pr_gotv = ProductPlanCategory::create([
            'id' => 31,
            'api_id' => 6,
            'product_plan_category_name' => 'GOTV',
            'product_id' => $product_cable->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);


        $pr_startimes = ProductPlanCategory::create([
            'id' => 32,
            'api_id' => 32,
            'product_plan_category_name' => 'STARTIMES',
            'product_id' => $product_cable->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);

        $pr_dstv = ProductPlanCategory::create([
            'id' => 33,
            'api_id' => 31,
            'product_plan_category_name' => 'DSTV',
            'product_id' => $product_cable->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);

        $pr_bills_prepaid = ProductPlanCategory::create([
            'id' => 34,
            'api_id' => 2,
            'product_plan_category_name' => 'PREPAID',
            'product_id' => $product_bills->id,
            'is_hot_sales' => 0,
            // 'bulk_data_wallet_in_mb' => 1048576
        ]);

        $users = User::with(['role' => function($query){
            $query->where('role_name','User');
        }])->get();
        
        //
        // ----------------------------------------------------------
        //  NEW: Affiliates + Mirrored Defaults (appended, not changed)
        // ----------------------------------------------------------
        //



        // Prepare collections we will mirror from (global defaults)
        $globalProductPlanCategories = DB::table('product_plan_categories')->get();
        $globalProductPlans = DB::table('product_plans')->get();
        $globalFundingOptions = DB::table('funding_options')->get();
        $globalFundingBankCodes = DB::table('funding_option_bank_codes')->get();
        $globalBulkDataPlans = DB::table('bulk_data_product_plans')->get();
        $landing_pages_config = config('landing_pages');
        $landing_pages2_config = config('landing_template2_pages');

        foreach ($affiliateSeeds as $affSeed) {
            // create affiliate if not exists (we use slug so affiliate_id columns that are strings match)
            $existingAffiliate = DB::table('affiliates')->where('slug', $affSeed['slug'])->first();
            if (!$existingAffiliate) {
                $affiliateId = DB::table('affiliates')->insertGetId([
                    'name' => $affSeed['name'],
                    'slug' => $affSeed['slug'],
                    'address' => $affSeed['address'],
                    'ip_address' => $affSeed['ip_address'],
                    'domain_url' => $affSeed['domain_url'],
                    'contact_phone' => $affSeed['contact_phone'],
                    'contact_email' => $affSeed['contact_email'],
                    'parent_email' => $affSeed['parent_email'],
                    'parent_key' => $affSeed['parent_key'],
                    'parent_plan_level' => $affSeed['parent_plan_level'],
                    'activation_status' => $affSeed['activation_status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $affiliateId = $existingAffiliate->id;
            }

            $affiliateSlug = $affSeed['slug']; // we'll store this into tables that expect string affiliate_id
            // $affiliateId = $affSeed['slug']; // we'll store this into tables that expect string affiliate_id

         
            //
            // 2) Referral settings (affiliate-scoped)
            //
            $refExists = DB::table('referral_settings')->where('affiliate_id', $affiliateId)->first();
            if (!$refExists) {
                DB::table('referral_settings')->insert([
                    'affiliate_id' => $affiliateId,
                    'first_downline_crediting_feature' => 3,
                    'set_first_downline_crediting_flat_rate' => 50,
                    'set_first_downline_crediting_percentage_rate' => 5,
                    'set_first_downline_crediting_cap' => 200,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            //
            // 3) Landing pages defaults (use your same config arrays)
            //
            if (is_array($landing_pages_config)) {
                foreach ($landing_pages_config as $key => $value) {
                    $existsLp = DB::table('landing_pages_settings')
                        ->where('affiliate_id', $affiliateId)
                        ->where('field_name', $key)
                        ->first();
                    if (!$existsLp) {
                        DB::table('landing_pages_settings')->insert([
                            'affiliate_id' => $affiliateId,
                            'template_type' => 'template_1',
                            'field_name' => $key,
                            'field_details' => $value[2] ?? '',
                            'visibility' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            if (is_array($landing_pages2_config)) {
                foreach ($landing_pages2_config as $key => $value) {
                    $existsLp2 = DB::table('landing_pages_settings')
                        ->where('affiliate_id', $affiliateId)
                        ->where('field_name', $key)
                        ->first();
                    if (!$existsLp2) {
                        DB::table('landing_pages_settings')->insert([
                            'affiliate_id' => $affiliateId,
                            'template_type' => 'template_2',
                            'field_name' => $key,
                            'field_details' => $value[2] ?? '',
                            'visibility' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            //
            // 4) Funding options -> affiliate_funding_options + affiliate_funding_option_bank_codes
            //
            foreach ($globalFundingOptions as $fund) {
                // create affiliate funding option entry if missing
                $existsFund = DB::table('affiliate_funding_options')
                    ->where('affiliate_id', $affiliateId)
                    ->where('funding_option_id', $fund->id)
                    ->first();

                if (!$existsFund) {
                    $affFundingId = DB::table('affiliate_funding_options')->insertGetId([
                        'affiliate_id' => $affiliateId,
                        'funding_option_id' => $fund->id,
                        'is_current_option' => $fund->is_current_option ?? 0,
                        'funding_option_name' => $fund->funding_option_name,
                        'slug' => $fund->slug,
                        'biz_bvn' => $fund->biz_bvn ?? null,
                        'api_public_key' => $fund->api_public_key ?? null,
                        'api_secret_key' => $fund->api_secret_key ?? null,
                        'activation_status' => $fund->activation_status ?? null,
                        'bank_name' => $fund->bank_name ?? null,
                        'bank_charges' => $fund->bank_charges ?? null,
                        'contract_code' => $fund->contract_code ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $affFundingId = $existsFund->id;
                }

                // mirror any funding_option_bank_codes for this funding option
                $bankCodesForFund = DB::table('funding_option_bank_codes')->where('funding_option_id', $fund->id)->get();
                foreach ($bankCodesForFund as $bank) {
                    $existsAffBank = DB::table('affiliate_funding_option_bank_codes')
                        ->where('affiliate_id', $affiliateId)
                        ->where('funding_option_bank_code_id', $bank->id)
                        ->first();
                    if (!$existsAffBank) {
                        DB::table('affiliate_funding_option_bank_codes')->insert([
                            'affiliate_id' => $affiliateId,
                            'funding_option_bank_code_id' => $bank->id,
                            'funding_option_id' => $affFundingId,
                            'bank_code' => $bank->bank_code,
                            'visibility_status' => $bank->visibility_status ?? 0,
                            'short_description' => $bank->short_description ?? null,
                            'rate_category' => $bank->rate_category ?? 'Flat',
                            'capped_at' => $bank->capped_at ?? '100',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            //
            // 5) Product plan categories -> affiliate_product_plan_categories
            //
            foreach ($globalProductPlanCategories as $cat) {
                $existsCat = DB::table('affiliate_product_plan_categories')
                    ->where('affiliate_id', $affiliateId)
                    ->where('plan_category_id', $cat->id)
                    ->first();
                if (!$existsCat) {
                    DB::table('affiliate_product_plan_categories')->insert([
                        'affiliate_id' => $affiliateId,
                        'plan_category_id' => $cat->id,
                        'product_plan_category_name' => $cat->product_plan_category_name,
                        'referral_commission_feature' => $cat->referral_commission_feature ?? 1,
                        'referral_commission_method' => $cat->referral_commission_method ?? 'percent',
                        'referral_commission_value' => $cat->referral_commission_value ?? 5,
                        // 'automation_id' => $cat->automation_id,
                        'product_id' => $cat->product_id,
                        'is_hot_sales' => $cat->is_hot_sales ?? 0,
                        'visibility' => $cat->visibility ?? 1,
                        'network_id' => $cat->network_id ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            //
            // 6) Product plans -> affiliate_product_plans
            //
            foreach ($globalProductPlans as $pp) {
                $existsPP = DB::table('affiliate_product_plans')
                    ->where('affiliate_id', $affiliateId)
                    ->where('product_plan_id', $pp->id)
                    ->first();

                if (!$existsPP) {
                    DB::table('affiliate_product_plans')->insert([
                        'affiliate_id' => $affiliateId,
                        'product_plan_name' => $pp->product_plan_name,
                        'product_plan_id' => $pp->id,
                        'plan_category_id' => $pp->product_plan_category_id,
                        'automation_product_plan_id' => $pp->automation_product_plan_id,
                        'automation_id' => $pp->automation_id,
                        'cost_price' => $pp->cost_price,
                        'api_id' => $pp->api_id ?? null,
                        'data_size_in_mb' => $pp->data_size_in_mb,
                        'validity_in_days' => $pp->validity_in_days,
                        'default_selling_profit' => $pp->default_selling_price ?? $pp->default_selling_profit ?? null,
                        'user_level_1_selling_profit' => $pp->user_level_1_selling_price ?? $pp->user_level_1_selling_profit ?? null,
                        'user_level_2_selling_profit' => $pp->user_level_2_selling_price ?? $pp->user_level_2_selling_profit ?? null,
                        'user_level_3_selling_profit' => $pp->user_level_3_selling_price ?? $pp->user_level_3_selling_profit ?? null,
                        'user_level_4_selling_profit' => $pp->user_level_4_selling_price ?? $pp->user_level_4_selling_profit ?? null,
                        'user_level_5_selling_profit' => $pp->user_level_5_selling_price ?? $pp->user_level_5_selling_profit ?? null,
                        'user_level_6_selling_profit' => $pp->user_level_6_selling_price ?? $pp->user_level_6_selling_profit ?? null,
                        'upline_commission_option' => $pp->upline_commission_option ?? 'flat',
                        'upline_percentage_commission' => $pp->upline_percentage_commission ?? 0,
                        'upline_flat_commission' => $pp->upline_flat_commission ?? 0,
                        'upline_commission_cap' => $pp->upline_commission_cap ?? 1000,
                        'visibility' => $pp->visibility ?? 1,
                        'public_visibility' => $pp->public_visibility ?? 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            //
            // 7) Bulk data product plans -> create affiliate-scoped copies
            //
            // foreach ($globalBulkDataPlans as $bp) {
            //     $existsBP = DB::table('bulk_data_product_plans')
            //         ->where('affiliate_id', $affiliateId)
            //         ->where('bulk_data_plan_name', $bp->bulk_data_plan_name)
            //         ->first();

            //     if (!$existsBP) {
            //         DB::table('bulk_data_product_plans')->insert([
            //             'affiliate_id' => $affiliateId,
            //             'bulk_data_plan_name' => $bp->bulk_data_plan_name,
            //             'product_plan_category_id' => $bp->product_plan_category_id,
            //             'data_value_mb' => $bp->data_value_mb,
            //             'data_value_gb' => $bp->data_value_gb,
            //             'data_value_tb' => $bp->data_value_tb,
            //             'mb_data_measurement' => $bp->mb_data_measurement ?? 1024,
            //             'cost_price' => $bp->cost_price,
            //             'default_selling_price' => $bp->default_selling_price,
            //             'user_level_1_selling_price' => $bp->user_level_1_selling_price,
            //             'user_level_2_selling_price' => $bp->user_level_2_selling_price,
            //             'user_level_3_selling_price' => $bp->user_level_3_selling_price,
            //             'user_level_4_selling_price' => $bp->user_level_4_selling_price,
            //             'user_level_5_selling_price' => $bp->user_level_5_selling_price,
            //             'user_level_6_selling_price' => $bp->user_level_6_selling_price,
            //             'visibility' => $bp->visibility ?? 1,
            //             'created_at' => now(),
            //             'updated_at' => now(),
            //         ]);
            //     }
            // }

            //
            // 8) Admin/general defaults for the affiliate (site logo, 2fa, template, colors) if missing
            //
            $exists_admin_general = DB::table('admin_general_settings')->where('affiliate_id', $affiliateId)->first();
            if (!$exists_admin_general) {
                DB::table('admin_general_settings')->insert([
                    'affiliate_id' => $affiliateId,
                    'site_logo_path' => '/images/logo-' . $affiliateId . '.png',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $exists_admin2fa = DB::table('admin2fa_settings')->where('affiliate_id', $affiliateId)->first();
            if (!$exists_admin2fa) {
                DB::table('admin2fa_settings')->insert([
                    'affiliate_id' => $affiliateId,
                    'global_user_2fa_setting' => 'OFF',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $exists_site_template = DB::table('site_templates')->where('affiliate_id', $affiliateId)->first();
            if (!$exists_site_template) {
                DB::table('site_templates')->insert([
                    'affiliate_id' => $affiliateId,
                    'template_name' => 'template_1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $exists_color = DB::table('admin_color_settings')->where('affiliate_id', $affiliateId)->first();
            if (!$exists_color) {
                DB::table('admin_color_settings')->insert([
                    'affiliate_id' => $affiliateId,
                    'color_name' => 'primary',
                    'color_value' => '#0d6efd',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

        } // end affiliates loop

        // End appended affiliate/mirroring logic
    }
}
