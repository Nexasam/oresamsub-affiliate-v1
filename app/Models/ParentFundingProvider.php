<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentFundingProvider extends Model
{
    protected $guarded = [];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return ['credentials' => 'encrypted:array', 'settings' => 'array', 'active' => 'boolean', 'generation_enabled' => 'boolean'];
    }

    public function parentBusiness(): BelongsTo
    {
        return $this->belongsTo(ParentBusiness::class);
    }

    public function fundingProvider(): BelongsTo
    {
        return $this->belongsTo(FundingProvider::class);
    }

    public function affiliateConfigurations(): HasMany
    {
        return $this->hasMany(AffiliateFundingProviderConfig::class);
    }
}
