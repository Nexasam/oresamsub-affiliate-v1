<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliateFundingProviderConfig extends Model
{
    protected $guarded = [];

    protected $hidden = ['credentials', 'webhook_secret'];

    protected function casts(): array
    {
        return ['credentials' => 'encrypted:array', 'settings' => 'array', 'bank_codes' => 'array', 'webhook_secret' => 'encrypted', 'webhook_active' => 'boolean', 'active' => 'boolean', 'generation_enabled' => 'boolean'];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function parentFundingProvider(): BelongsTo
    {
        return $this->belongsTo(ParentFundingProvider::class);
    }

    public function modeChangeRequests(): HasMany
    {
        return $this->hasMany(FundingModeChangeRequest::class);
    }

    public function banks(): HasMany
    {
        return $this->hasMany(AffiliateFundingProviderBank::class);
    }
}
