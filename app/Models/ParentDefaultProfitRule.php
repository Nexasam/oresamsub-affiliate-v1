<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentDefaultProfitRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['value' => 'decimal:2'];
    }

    public function parentBusiness(): BelongsTo
    {
        return $this->belongsTo(ParentBusiness::class);
    }

    public function parentResellerLevel(): BelongsTo
    {
        return $this->belongsTo(ParentResellerLevel::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
