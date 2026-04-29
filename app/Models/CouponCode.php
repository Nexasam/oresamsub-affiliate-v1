<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CouponCode extends AffiliateScopedModel
{
    use HasFactory;

    protected $guarded = [];

    /**
    * each product_plan_category plan belongs to a product_plan_category 
    **/
    public function product_plan_category()
    {
        return $this->belongsTo(ProductPlanCategory::class, 'product_plan_category_id', 'id');
    }

    
}
