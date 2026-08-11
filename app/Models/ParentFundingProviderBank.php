<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentFundingProviderBank extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['rate_value' => 'decimal:2', 'percentage_cap' => 'decimal:2', 'active' => 'boolean', 'generation_enabled' => 'boolean'];
    }

    public function parentFundingProvider(): BelongsTo
    {
        return $this->belongsTo(ParentFundingProvider::class);
    }

    public function affiliateSettings(): HasMany
    {
        return $this->hasMany(AffiliateFundingProviderBank::class);
    }
}
