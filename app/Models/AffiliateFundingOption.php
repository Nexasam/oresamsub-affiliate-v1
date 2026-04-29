<?php

namespace App\Models;


use App\Models\AdminWebhookString;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffiliateFundingOption extends AffiliateScopedModel
{
    use HasFactory;

    protected $guarded = [];
    public function bank_codes(){
        return $this->hasMany(AffiliateFundingOptionBankCodes::class,'funding_option_id','id');
    }

    public function webhook_string(){
        return $this->hasOne(AdminWebhookString::class,'funding_option_id','id');
    }

   

}
