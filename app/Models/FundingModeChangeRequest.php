<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundingModeChangeRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function affiliateConfiguration(): BelongsTo
    {
        return $this->belongsTo(AffiliateFundingProviderConfig::class, 'affiliate_funding_provider_config_id');
    }
}
