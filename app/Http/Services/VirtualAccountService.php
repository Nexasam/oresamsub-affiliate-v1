<?php

namespace App\Http\Services;

use App\Http\Services\XixaPayService;
use App\Http\Services\CrystalPayService;

class VirtualAccountService{

    public function generate_accounts($data){
            $dataaa['user'] = $data['user'];
            // (new CrystalPayService())->generate_accounts($dataaa);
            
            $xixa =  (new XixaPayService())->generate_accounts($dataaa);
            logger("XIXA Repsone: ".json_encode($xixa));
            if($xixa['status'] == 1){
                return [
                    'status' => 1,
                    'message' => 'Virtual Accounts Generated Successfully',
                ];
            }

            return [
                'status' => -1,
                'message' => 'Account(s) could not be generated',
            ];
  

            
        
    }


    

}