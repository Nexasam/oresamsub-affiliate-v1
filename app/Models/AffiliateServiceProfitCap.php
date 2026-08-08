<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class AffiliateServiceProfitCap extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (AffiliateServiceProfitCap $cap) {
            if ($cap->customer_level < 1 || $cap->customer_level > 6) {
                throw new InvalidArgumentException('Customer level must be between 1 and 6.');
            }
            if (! in_array($cap->calculation_type, ['flat', 'percent'], true) || $cap->max_value < 0) {
                throw new InvalidArgumentException('Invalid affiliate service profit cap.');
            }
            if ($cap->calculation_type === 'percent' && $cap->max_value > 100) {
                throw new InvalidArgumentException('Percentage cap cannot exceed 100%.');
            }
        });
    }

    protected function casts(): array
    {
        return ['customer_level' => 'integer', 'max_value' => 'decimal:2'];
    }

    public function parentBusiness(): BelongsTo
    {
        return $this->belongsTo(ParentBusiness::class);
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
