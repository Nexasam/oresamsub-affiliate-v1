<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffiliateProductPlan extends AffiliateScopedModel
{
    use HasFactory;

    //TODO: revamp productplan with global scope for visibility in all its instance in the code

    protected $guarded = ['id'];

    /**
     * each card belongs to a product plan 
    **/
    public function product_plan()
    {
        return $this->belongsTo(ProductPlan::class, 'product_plan_id', 'id');
    }

    
     /**
     * each product plan belongs to a product 
    **/
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
        // return $this->belongsTo(Product::class, 'product_id', 'id')->where('active_status',1);
    }

     /**
     * each product plan belongs to a product_plan_category === nullable 
    **/
    public function product_plan_category()
    {
        return $this->belongsTo(AffiliateProductPlanCategory::class, 'plan_category_id', 'id');
        // return $this->belongsTo(ProductPlanCategory::class, 'product_plan_category_id', 'id')->where('active_status',1);
    }



    //likely redundant
    public function automation()
    {
        return $this->belongsTo(Automation::class, 'automation_id', 'id');
        // return $this->belongsTo(ProductPlanCategory::class, 'product_plan_category_id', 'id')->where('active_status',1);
    }


    //likely redundant
    public function reprocess_automation()
    {
        return $this->belongsTo(Automation::class, 'reprocess_automation_id', 'id');
        // return $this->belongsTo(ProductPlanCategory::class, 'product_plan_category_id', 'id')->where('active_status',1);
    }
    

    
}
