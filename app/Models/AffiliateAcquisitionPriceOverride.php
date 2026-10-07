<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateAcquisitionPriceOverride extends Model
{
    protected $guarded = [];

    public function parentBusiness(): BelongsTo
    {
        return $this->belongsTo(ParentBusiness::class);
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function productPlan(): BelongsTo
    {
        return $this->belongsTo(ProductPlan::class);
    }

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'max_profit' => 'decimal:2',
        ];
    }
}
