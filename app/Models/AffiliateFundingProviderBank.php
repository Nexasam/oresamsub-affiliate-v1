<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateFundingProviderBank extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['rate_value' => 'decimal:2', 'percentage_cap' => 'decimal:2', 'active' => 'boolean', 'generation_enabled' => 'boolean'];
    }

    public function affiliateConfiguration(): BelongsTo
    {
        return $this->belongsTo(AffiliateFundingProviderConfig::class, 'affiliate_funding_provider_config_id');
    }

    public function parentBank(): BelongsTo
    {
        return $this->belongsTo(ParentFundingProviderBank::class, 'parent_funding_provider_bank_id');
    }
}
