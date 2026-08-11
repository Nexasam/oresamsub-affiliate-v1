<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliateSettlementWallet extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'available_balance' => 'decimal:2',
            'reserved_balance' => 'decimal:2',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function parentBusiness(): BelongsTo
    {
        return $this->belongsTo(ParentBusiness::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(AffiliateSettlementLedgerEntry::class);
    }
}
