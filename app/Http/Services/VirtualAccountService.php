<?php

namespace App\Http\Services;

use App\Services\Funding\MultiParentVirtualAccountService;

class VirtualAccountService
{
    public function generate_accounts($data)
    {
        $dataaa['user'] = $data['user'];
        if (config('parent_businesses.features.multi_parent_funding', false)) {
            $normalized = app(MultiParentVirtualAccountService::class)->generateForUser($dataaa['user']);
            if ($normalized['handled']) {
                return $normalized;
            }
        }
        // (new CrystalPayService())->generate_accounts($dataaa);

        // $xixa =  (new XixaPayService())->generate_accounts($dataaa);
        // logger("XIXA Repsone: ".json_encode($xixa));

        $sec = (new SecurewavengService)->generate_accounts($dataaa);

        if ($sec['status'] == 1) {
            return [
                'status' => 1,
                'message' => $sec['message'],
            ];
        }

        return [
            'status' => -1,
            'message' => 'Account(s) could not be generated:  '.$sec['message'],
        ];

    }
}
