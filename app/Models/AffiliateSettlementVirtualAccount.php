<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateSettlementVirtualAccount extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['provider_metadata' => 'array'];
    }

    public function affiliate(): BelongsTo { return $this->belongsTo(Affiliate::class); }
    public function parentBusiness(): BelongsTo { return $this->belongsTo(ParentBusiness::class); }
    public function parentFundingProvider(): BelongsTo { return $this->belongsTo(ParentFundingProvider::class); }
}
