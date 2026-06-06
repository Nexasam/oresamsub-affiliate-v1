<?php

namespace App\Http\Services;

use App\Models\ProductPlan;
use App\Models\UserVirtualAccount;

class SafehavenAutomation{

    protected $baseUrl; 
    protected $clientId = 'f3fca89d4b436a6cf1f76f48e98a133e';


    // cable purchase success
    // {
    //     "buy": {
    //     "status": 1,
    //     "message": {
    //     "statusCode": 200,
    //     "message": "Cable TV purchased successfully.",
    //     "data": {
    //     "clientId": "64da5f68f950cf0024d67ddf",
    //     "serviceCategoryId": "61efad45da92348f9dde5fad",
    //     "reference": "5785a407762e4f8186863e26d6793a9e",
    //     "status": "successful",
    //     "amount": 2000,
    //     "id": "699888b69a81fe3641482dc3",
    //     "receiver": {
    //     "number": "8072391211",
    //     "name": null,
    //     "customerNumber": null,
    //     "distribution": "GOTV",
    //     "vendType": ""
    //     }
    //     }
    //     }
    //     }
    //     }

    //cable verification response
    // {
    //     "verify": {
    //     "status": 1,
    //     "message": {
    //     "statusCode": 200,
    //     "message": "Cable TV number verified successfully.",
    //     "sessionId": "090286260220070815824183052661",
    //     "data": {
    //     "name": "olusola Samuelworldscreen"
    //     }
    //     }
    //     }
    //     }

    
    //service
    // [
    //     { "_id": "61efab78b5ce7eaad3b405d0", "identifier": "UTILITY" },
    //     { "_id": "61efaba1da92348f9dde5f6c", "identifier": "AIRTIME" },
    //     { "_id": "61efabb2da92348f9dde5f6e", "identifier": "DATA" },
    //     { "_id": "61efabbeda92348f9dde5f70", "identifier": "CABLETV" }
    //   ]


    //service cable categories
    // {
    //     "_id": "61efad38da92348f9dde5faa",
    //     "service": "61efabbeda92348f9dde5f70",
    //     "identifier": "DSTV"
    //   },
    //   {
    //     "_id": "61efad45da92348f9dde5fad",
    //     "service": "61efabbeda92348f9dde5f70",
    //     "identifier": "GOTV"
    //   },
    //   {
    //     "_id": "61efad50da92348f9dde5fb0",
    //     "service": "61efabbeda92348f9dde5f70",
    //     "identifier": "STARTIMES"
    //   }

    // products 4 cable
    // [
    //     { "category": "dstv", "name": "Box Office valid for 1 month", "bundleCode": "ng_dstv_boxoffice", "amount": 0 },
    //     { "category": "dstv", "name": "DStv Compact Plus valid for 1 month", "bundleCode": "ng_dstv_comple36", "amount": 30000 },
    //     { "category": "dstv", "name": "DStv Compact valid for 1 month", "bundleCode": "ng_dstv_compe36", "amount": 19000 },
    //     { "category": "dstv", "name": "DStv Confam valid for 1 month", "bundleCode": "ng_dstv_nnj2e36", "amount": 11000 },
    //     { "category": "dstv", "name": "DStv Indian valid for 1 month", "bundleCode": "ng_dstv_asiae36", "amount": 14900 },
    //     { "category": "dstv", "name": "DStv Padi  valid for 1 month", "bundleCode": "ng_dstv_nltese36", "amount": 4400 },
    //     { "category": "dstv", "name": "DStv Premium + French valid for 1 month", "bundleCode": "ng_dstv_prwfrnse36", "amount": 69000 },
    //     { "category": "dstv", "name": "DStv Premium W/Afr + Asian Bouquet E36 valid for 1 month", "bundleCode": "ng_dstv_prwasie36", "amount": 50500 },
    //     { "category": "dstv", "name": "DStv Premium valid for 1 month", "bundleCode": "ng_dstv_prwe36", "amount": 44500 },
    //     { "category": "dstv", "name": "DStv Yanga  valid for 1 month", "bundleCode": "ng_dstv_nnj1e36", "amount": 6000 },
    //     { "category": "dstv", "name": "Top Up", "bundleCode": "TOP_UP", "amount": null },
      
    //     { "category": "gotv", "name": "GOtv Jinja valid for 1 month", "bundleCode": "ng_gotv_gotvnj1", "amount": 3900 },
    //     { "category": "gotv", "name": "GOtv Jolli valid for 1 month", "bundleCode": "ng_gotv_gotvnj2", "amount": 5800 },
    //     { "category": "gotv", "name": "GOtv Max valid for 1 month", "bundleCode": "ng_gotv_gotvmax", "amount": 8500 },
    //     { "category": "gotv", "name": "GOtv Smallie-monthly valid for 1 month", "bundleCode": "ng_gotv_gohan", "amount": 1900 },
    //     { "category": "gotv", "name": "GOtv Supa  valid for 1 month", "bundleCode": "ng_gotv_gotvsupa", "amount": 11400 },
    //     { "category": "gotv", "name": "GOtv Supa Plus Bouquet valid for 1 month", "bundleCode": "ng_gotv_gotvsupaplus", "amount": 16800 },
    //     { "category": "gotv", "name": "Top Up", "bundleCode": "TOP_UP", "amount": null },
      
    //     { "category": "startimes", "name": "Basic (Antenna) - Monthly", "bundleCode": "basic", "amount": 4000 },
    //     { "category": "startimes", "name": "Basic (Antenna) - Weekly", "bundleCode": "basicweek", "amount": 1400 },
    //     { "category": "startimes", "name": "Basic (Dish) - Monthly", "bundleCode": "smart", "amount": 5100 },
    //     { "category": "startimes", "name": "Basic (Dish) - Weekly", "bundleCode": "smartweek", "amount": 1700 },
    //     { "category": "startimes", "name": "Classic (Antenna) - Monthly", "bundleCode": "classic", "amount": 6000 },
    //     { "category": "startimes", "name": "Classic (Antenna) - Weekly", "bundleCode": "classicweek", "amount": 2000 },
    //     { "category": "startimes", "name": "Classic (Dish) - Monthly", "bundleCode": "special", "amount": 7400 },
    //     { "category": "startimes", "name": "Classic (Dish) - Weekly", "bundleCode": "specialweek", "amount": 2500 },
    //     { "category": "startimes", "name": "Nova (Antenna) - Monthly", "bundleCode": "nova", "amount": 2100 },
    //     { "category": "startimes", "name": "Nova (Antenna) - Weekly", "bundleCode": "novaweek", "amount": 700 },
    //     { "category": "startimes", "name": "Nova (Dish) - Monthly", "bundleCode": "novadish", "amount": 2100 },
    //     { "category": "startimes", "name": "Nova (Dish) - Weekly", "bundleCode": "novadishweek", "amount": 700 },
    //     { "category": "startimes", "name": "Startimes Chinese (Dish) - Monthly", "bundleCode": "chinese", "amount": 21000 },
    //     { "category": "startimes", "name": "Super (Antenna) - Monthly", "bundleCode": "super-antenna", "amount": 9500 },
    //     { "category": "startimes", "name": "Super (Antenna) - Weekly", "bundleCode": "superweek-antenna", "amount": 3200 },
    //     { "category": "startimes", "name": "Super (Dish) - Monthly", "bundleCode": "super", "amount": 9800 },
    //     { "category": "startimes", "name": "Super (Dish) - Weekly", "bundleCode": "superweek", "amount": 3300 },
    //     { "category": "startimes", "name": "Top Up", "bundleCode": "TOP_UP", "amount": null }
    //   ]

    // cats for electricity
    // [
    //     {
    //       "_id": "61efac19b5ce7eaad3b405d4",
    //       "name": "BEDC",
    //       "identifier": "BENIN",
    //       "logoUrl": "https://res.cloudinary.com/sudo-africa/image/upload/v1646208565/SafeHavenVAS/bedc_transparent_logo_1_vortpx.png"
    //     },
    //     {
    //       "_id": "61efac27da92348f9dde5f74",
    //       "name": "EKEDC",
    //       "identifier": "EKO",
    //       "logoUrl": "https://res.cloudinary.com/sudo-africa/image/upload/v1646208567/SafeHavenVAS/ekedc_logo_1_gvanzy.png"
    //     },
    //     {
    //       "_id": "61efac35da92348f9dde5f77",
    //       "name": "AEDC",
    //       "identifier": "ABUJA",
    //       "logoUrl": "https://res.cloudinary.com/sudo-africa/image/upload/v1646208565/SafeHavenVAS/aedc_logo_1_u4foxr.png"
    //     },
    //     {
    //       "_id": "61efac42da92348f9dde5f7a",
    //       "name": "EEDC",
    //       "identifier": "ENUGU",
    //       "logoUrl": "https://res.cloudinary.com/sudo-africa/image/upload/v1646208566/SafeHavenVAS/eedc_logo_1_pggere.png"
    //     },
    //     {
    //       "_id": "61efac51da92348f9dde5f7d",
    //       "name": "IBEDC",
    //       "identifier": "IBADAN",
    //       "logoUrl": "https://res.cloudinary.com/sudo-africa/image/upload/v1646208568/SafeHavenVAS/ibedc_1_zquagj.png"
    //     },
    //     {
    //       "_id": "61efac5eda92348f9dde5f80",
    //       "name": "IKEDC",
    //       "identifier": "IKEJA",
    //       "logoUrl": "https://res.cloudinary.com/sudo-africa/image/upload/v1646208566/SafeHavenVAS/Ikeja-Electric-Logo-new-1_1_xzufx0.png"
    //     },
    //     {
    //       "_id": "61efac6ada92348f9dde5f83",
    //       "name": "JEDC",
    //       "identifier": "JOS",
    //       "logoUrl": "https://res.cloudinary.com/sudo-africa/image/upload/v1646208567/SafeHavenVAS/Jos-Electricity-Distribution-Company_1_ybqwmz.png"
    //     },
    //     {
    //       "_id": "61efac78da92348f9dde5f86",
    //       "name": "KAEDC",
    //       "identifier": "KADUNA",
    //       "logoUrl": "https://res.cloudinary.com/sudo-africa/image/upload/v1646208565/SafeHavenVAS/34-341783_kaduna-electricity-distribution-company-kaduna-electricity-distribution-company_1_cvxnol.png"
    //     },
    //     {
    //       "_id": "61efac87da92348f9dde5f89",
    //       "name": "KEDCO",
    //       "identifier": "KANO",
    //       "logoUrl": "https://res.cloudinary.com/sudo-africa/image/upload/v1646208567/SafeHavenVAS/Kedco_Logo_web_1_hbbhfj.png"
    //     },
    //     {
    //       "_id": "61efac94da92348f9dde5f8c",
    //       "name": "PHEDC",
    //       "identifier": "PH",
    //       "logoUrl": "https://res.cloudinary.com/sudo-africa/image/upload/v1646208567/SafeHavenVAS/PHED_1_rpevjz.png"
    //     },
    //     {
    //       "_id": "61efaca1da92348f9dde5f8f",
    //       "name": "YEDC",
    //       "identifier": "YOLA",
    //       "logoUrl": "https://res.cloudinary.com/sudo-africa/image/upload/v1646208568/SafeHavenVAS/yedc_logo_2_cgumad.png"
    //     }
    //   ]


    //VENDTYPE: PREPAID FOR NOW
    // {"statusCode":200,"message":"Power Data verified successfully.","sessionId":"090286260223162615572716216929","data":{"discoCode":"IBADAN","vendType":"PREPAID","meterNo":"45082894648","minVendAmount":100,"maxVendAmount":200000,"outstanding":0,"debtRepayment":0,"name":"ODOMO SUNDAY .","address":"1,ABE COCOA JUNCTIONILUPEJU AREAOSOGBO OSUN","tariff":"Not Available","tariffClass":"Not Available","demandCategory":"NMD"}}

    //payElectricityRespon:
    // {
    //     "statusCode": 200,
    //     "message": "Utility Package purchased successfully.",
    //     "data": {
    //       "clientId": "64da5f68f950cf0024d67ddf",
    //       "serviceCategoryId": "61efac51da92348f9dde5f7d",
    //       "reference": "33d5207fc22f46939cc0001f989faa51",
    //       "verificationId": null,
    //       "status": "successful",
    //       "amount": 400,
    //       "id": "699c80529f597ecc4f850d7f",
    //       "utilityToken": "05564438259985041532",
    //       "tokenValue": "1.78",
    //       "metaData": {
    //         "id": 178635204,
    //         "amountGenerated": 400,
    //         "tariff": null,
    //         "debtAmount": 0,
    //         "debtRemaining": 0,
    //         "disco": "IBADAN",
    //         "orderId": "33d5207fc22f46939cc0001f989faa51",
    //         "receiptNo": "0000036004288",
    //         "tax": 0,
    //         "vendTime": "2026-02-23 17:29:12",
    //         "token": "0556-4438-2599-8504-1532",
    //         "totalAmountPaid": 400,
    //         "units": 1.78,
    //         "vendAmount": 0,
    //         "vendRef": "0000036004288",
    //         "responseCode": 100,
    //         "responseMessage": "SUCCESSFUL",
    //         "address": "1,ABE COCOA JUNCTIONILUPEJU AREAOSOGBO OSUN",
    //         "name": "ODOMO SUNDAY   .",
    //         "phoneNo": null,
    //         "charges": "0",
    //         "tariffIndex": null,
    //         "parcels": [
    //           {
    //             "type": "TOKEN",
    //             "content": "05564438259985041532"
    //           }
    //         ],
    //         "demandCategory": "NMD",
    //         "assetProvider": "IBADAN",
    //         "tariffClass": "Not Available"
    //       },
    //       "receiver": {
    //         "number": "45082894648",
    //         "name": null,
    //         "address": null,
    //         "distribution": null,
    //         "vendType": null
    //       }
    //     }
    //   }


    public function generateToken(){
        $curl = curl_init();

        $postarr = [
            'grant_type' => 'client_credentials',
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJmb3hkYXRhaHViLmNvbSIsInN1YiI6ImYzZmNhODlkNGI0MzZhNmNmMWY3NmY0OGU5OGExMzNlIiwiYXVkIjoiaHR0cHM6Ly9hcGkuc2FmZWhhdmVubWZiLmNvbSIsImlhdCI6MTc3MTUzMjg4MSwiZXhwIjoyMDU1NTE4NTc1fQ.GMrxwaRrmlqRYBQhyNoit2Flm6dSxRJPDJo5tuvp7Bfc8w0mj9h4yJgmzuVFeh6uJ3TRXAzAQ154rrdK2bTNGW6Zft7vgf1NV7KIaq0pHVJ4QDbxk601VvvBILP5cdLjAmR6qPdMmVXS4q2CQnW-CW3oBgt5KwmmkWKbRcLFa5Y',
            'client_id' => 'f3fca89d4b436a6cf1f76f48e98a133e'
        ];

        $baseurl = "https://api.safehavenmfb.com";

        $postjson = json_encode($postarr);

        curl_setopt_array($curl, array(
        CURLOPT_URL => "$baseurl/oauth2/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>$postjson,
        CURLOPT_HTTPHEADER => array(
        'accept: application/json',
        'content-type: application/json'
        ),
        ));

        $response = curl_exec($curl);
        logger('initiateuuu: '.$response);

        curl_close($curl);
        $decode_response = json_decode($response,true);

        if( isset($decode_response['access_token'])){
            logger('this rannnnn');
            return [
                'status' => 1,
                'message' => $decode_response,
            ];
        }

        return [
            'status' => -1,
            'message' => $decode_response['error_description'] ?? 'Token could not be generated',
        ];

    }

    /**
     * Get Services
     */
    public function getServices()
    {
      
        // $generated_token = $data['token'];
        $generated_token = $this->generateToken()['message']['access_token'] ?? null;
        $cli = $this->generateToken()['message']['ibs_client_id'] ?? null;
    
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.safehavenmfb.com/vas/services",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
             'accept: application/json',
            'content-type: application/json',
            'ClientID: '.$cli,
            'Authorization: Bearer '.$generated_token
            ],
        ]);

        $response = curl_exec($curl);
        logger('get services: '.$response);

        curl_close($curl);
       
        $decode_response = json_decode($response,true);

        if(isset($decode_response['statusCode']) && $decode_response['statusCode'] == 200 )
       {        // if( isset($decode_response['data']['_id']) ){
            return [
                'status' => 1,
                'message' => $decode_response,
            ];
        }

        logger('this rannnnn for get services:'.$response);
        
        return [
            'status' => -1,
            'message' => $decode_response['message'] ?? 'Services fetch failed',
        ];
    }

    public function getCableServiceCategories()
    {
        $id = "61efabbeda92348f9dde5f70"; // CableTV service id
        // $generated_token = $data['token'];
        $generated_token = $this->generateToken()['message']['access_token'] ?? null;
        $cli = $this->generateToken()['message']['ibs_client_id'] ?? null;

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.safehavenmfb.com/vas/service/{$id}/service-categories",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
             'accept: application/json',
            'content-type: application/json',
            'ClientID: '.$cli,
            'Authorization: Bearer '.$generated_token
            ],
        ]);

        $response = curl_exec($curl);
        logger('get cable service category: '.$response);

        curl_close($curl);
       
        $decode_response = json_decode($response,true);

        if(isset($decode_response['statusCode']) && $decode_response['statusCode'] == 200 )
       {        // if( isset($decode_response['data']['_id']) ){
            return [
                'status' => 1,
                'message' => $decode_response,
            ];
        }

        logger('this rannnnn for get cable service category:'.$response);
        
        return [
            'status' => -1,
            'message' => $decode_response['message'] ?? 'Cable Service Category fetch failed',
        ];
    }   

    public function getElectricityServiceCategories()
    {
        $id = "61efab78b5ce7eaad3b405d0"; // electriccity service id
        // $generated_token = $data['token'];
        $generated_token = $this->generateToken()['message']['access_token'] ?? null;
        $cli = $this->generateToken()['message']['ibs_client_id'] ?? null;

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.safehavenmfb.com/vas/service/{$id}/service-categories",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
             'accept: application/json',
            'content-type: application/json',
            'ClientID: '.$cli,
            'Authorization: Bearer '.$generated_token
            ],
        ]);

        $response = curl_exec($curl);
        logger('get cable service category: '.$response);

        curl_close($curl);
       
        $decode_response = json_decode($response,true);

        if(isset($decode_response['statusCode']) && $decode_response['statusCode'] == 200 )
       {        // if( isset($decode_response['data']['_id']) ){
            return [
                'status' => 1,
                'message' => $decode_response,
            ];
        }

        logger('this rannnnn for get electricity service category:'.$response);
        
        return [
            'status' => -1,
            'message' => $decode_response['message'] ?? 'Elecctricity Service Category fetch failed',
        ];
    }   

    

    public function getCableServiceProductsByCategoryId($catId)
    {
        
        $generated_token = $this->generateToken()['message']['access_token'] ?? null;
        $cli = $this->generateToken()['message']['ibs_client_id'] ?? null;

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.safehavenmfb.com/vas/service-category/{$catId}/products",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'content-type: application/json',
                'ClientID: '.$cli,
                'Authorization: Bearer '.$generated_token
              
            ],
          ]);

        $response = curl_exec($curl);
        logger('get cable service category plans: '.$response);

        curl_close($curl);
       
        $decode_response = json_decode($response,true);

        if(isset($decode_response['statusCode']) && $decode_response['statusCode'] == 200 )
       {        // if( isset($decode_response['data']['_id']) ){
            return [
                'status' => 1,
                'message' => $decode_response,
            ];
        }

        logger('this rannnnn for get cable service category plan:'.$response);
        
        return [
            'status' => -1,
            'message' => $decode_response['message'] ?? 'Cable Service Category plans fetch failed',
        ];
    } 
    
    public function getElectricityServiceProductsByCategoryId($catId)
    {
        
        $generated_token = $this->generateToken()['message']['access_token'] ?? null;
        $cli = $this->generateToken()['message']['ibs_client_id'] ?? null;

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.safehavenmfb.com/vas/service-category/{$catId}/products",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'content-type: application/json',
                'ClientID: '.$cli,
                'Authorization: Bearer '.$generated_token
              
            ],
          ]);

        $response = curl_exec($curl);
        logger('get elect service category prods: '.$response);

        curl_close($curl);
       
        $decode_response = json_decode($response,true);

        if(isset($decode_response['statusCode']) && $decode_response['statusCode'] == 200 )
       {        // if( isset($decode_response['data']['_id']) ){
            return [
                'status' => 1,
                'message' => $decode_response,
            ];
        }

        logger('this rannnnn for get elect service category plan:'.$response);
        
        return [
            'status' => -1,
            'message' => $decode_response['message'] ?? 'elect Service Category plans fetch failed',
        ];
    } 
    
    public function verifyCableElectricityByCategoryId($catId,$smartCard)
    {
        
        $generated_token = $this->generateToken()['message']['access_token'] ?? null;
        $cli = $this->generateToken()['message']['ibs_client_id'] ?? null;

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.safehavenmfb.com/vas/verify",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'entityNumber' => $smartCard,
                'serviceCategoryId' => $catId
            ]),
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'content-type: application/json',
                'ClientID: '.$cli,
                'Authorization: Bearer '.$generated_token
              
            ],
          ]);

        $response = curl_exec($curl);
        logger('verify cable electricity by category id: '.$response);

        curl_close($curl);
       
        $decode_response = json_decode($response,true);

        if(isset($decode_response['statusCode']) && $decode_response['statusCode'] == 200 )
       {        // if( isset($decode_response['data']['_id']) ){
            return [
                'status' => 1,
                'message' => $decode_response['message'] ?? 'Verification done successfully',
                'name' => $decode_response['data']['name'] ?? "Not found",
                'address' => $decode_response['data']['address'] ?? null,
            ];
        }

        logger('this rannnnn for verify cable electricity by category id:'.$response);
        
        return [
            'status' => -1,
            'name' => $decode_response['data']['name'] ?? 'Not found',
            'address' => $decode_response['data']['address'] ?? null,
            'message' => $decode_response['message'] ?? 'Verification was not successful',


        ];
    }


    public function cableSubscription($catId,$amount,$bundleCode,$smartCard)
    {
      
        $generated_token = $this->generateToken()['message']['access_token'] ?? null;
        $cli = $this->generateToken()['message']['ibs_client_id'] ?? null;

        $curl = curl_init();

        curl_setopt_array($curl, [
          CURLOPT_URL => "https://api.safehavenmfb.com/vas/pay/cable-tv",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode([
            'amount' => $amount,
            'channel' => 'WEB',
            'serviceCategoryId' => $catId,
            'bundleCode' => $bundleCode,
            // 'debitAccountNumber' => '0119587700', #this is the test account number provided by safehaven, will be replaced by the virtual account number of the user when fully integrated
            'debitAccountNumber' => '0119387340', #this is the test account number provided by safehaven, will be replaced by the virtual account number of the user when fully integrated
            'cardNumber' =>$smartCard,
            'externalReference' => rand(100000,999999).'_ref'.time(),
          ]),
          CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "content-type: application/json",
            'ClientID: '.$cli,
            'Authorization: Bearer '.$generated_token
          ],
        ]);
        

        $response = curl_exec($curl);
        logger('buy cable:: '.$response);

        curl_close($curl);
       
        $decode_response = json_decode($response,true);

        if(isset($decode_response['statusCode']) && $decode_response['statusCode'] == 200 )
       {        // if( isset($decode_response['data']['_id']) ){
            return [
                'status' => 1,
                'user_message' => $decode_response['message'] ?? 'Cable Purchased successfully',
                'admin_message' => $response,
            ];
        }

        logger('this rannnnn for cable purchase'.$response);
        
        return [
            'status' => -1,
            'user_message' => $decode_response['message'] ?? 'Cable Purchase failed',
            'admin_message' => $response,
        ];
    }

    public function payElectricity($planId,$amount,$vendType,$meterNumber)
    {    
        $channel = 'WEB';
        $debitAccountNumber = '0119387340'; #this is the test account number provided by safehaven, will be replaced by the virtual account number of the user when fully integrated
        $generated_token = $this->generateToken()['message']['access_token'] ?? null;
        $cli = $this->generateToken()['message']['ibs_client_id'] ?? null;

        $curl = curl_init();

        $plan = ProductPlan::select('automation_product_plan_id')->where('id',$planId)->first();

        if(!$plan){
            return [
                'status' => -1,
                'user_message' => 'Invalid plan selected',
                'admin_message' => 'Invalid plan selected',
            ];
        }

        $catId = $plan->automation_product_plan_id;
        
        curl_setopt_array($curl, [
          CURLOPT_URL => "https://api.safehavenmfb.com/vas/pay/utility",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode([
            'amount' => $amount,
            'channel' => $channel,
            'serviceCategoryId' => $catId,
            'vendType' => $vendType ?? 'PREPAID',
            'debitAccountNumber' => $debitAccountNumber, #this is the test account number provided by safehaven, will be replaced by the virtual account number of the user when fully integrated
            'meterNumber' =>$meterNumber,
            'externalReference' => rand(100000,999999).'_ref'.time(),
          ]),
          CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "content-type: application/json",
            'ClientID: '.$cli,
            'Authorization: Bearer '.$generated_token
          ],
        ]);
        

        $response = curl_exec($curl);
        logger('buy utility:: '.$response);

        curl_close($curl);
       
        $decode_response = json_decode($response,true);

        if(isset($decode_response['statusCode']) && $decode_response['statusCode'] == 200 )
       {        // if( isset($decode_response['data']['_id']) ){
            return [
                'status' => 1,
                'user_message' => $decode_response['message'] ?? 'Utility bill purchased successfully',
                'admin_message' => $response,
                'token' => $decode_response['data']['utilityToken'] ?? null,
                'token_value' => $decode_response['data']['tokenValue'] ?? null,
            ];
        }

        logger('this rannnnn for utility purchase'.$response);
        
        return [
            'status' => -1,
            'user_message' => $decode_response['message'] ?? 'Utility Purchase failed',
            'admin_message' => $response,
            'token' => null,
            'token_value' => null,
        ];
    }







    

}