<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FundingOption extends Model
{
    use HasFactory;

    protected $guarded = [];
    public function bank_codes(){
        return $this->hasMany(FundingOptionBankCodes::class,'funding_option_id','id');
    }

    // public function webhook_string(){
    //     return $this->hasOne(AdminWebhookString::class,'funding_option_id','id');
    // }

   

}
