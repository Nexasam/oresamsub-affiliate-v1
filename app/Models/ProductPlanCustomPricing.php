<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductPlanCustomPricing extends AffiliateScopedModel
{
    use HasFactory;

    protected $guarded = [];

     /**
    * each funding belongs to a user 
    **/
    public function user()
    {
        return $this->belongsTo(User::class);
    }

     /**
    * each funding belongs to a user 
    **/
    public function product_plan()
    {
        return $this->belongsTo(ProductPlan::class);
    }
}
