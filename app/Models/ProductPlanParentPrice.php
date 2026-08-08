<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPlanParentPrice extends Model
{
    protected $guarded = [];

    public function parentBusiness(): BelongsTo
    {
        return $this->belongsTo(ParentBusiness::class);
    }

    public function productPlan(): BelongsTo
    {
        return $this->belongsTo(ProductPlan::class);
    }

    public function parentResellerLevel(): BelongsTo
    {
        return $this->belongsTo(ParentResellerLevel::class);
    }

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'max_profit' => 'decimal:2',
        ];
    }
}
