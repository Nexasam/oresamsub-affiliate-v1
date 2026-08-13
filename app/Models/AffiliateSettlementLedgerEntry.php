<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateSettlementLedgerEntry extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(AffiliateSettlementWallet::class, 'affiliate_settlement_wallet_id');
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function parentBusiness(): BelongsTo
    {
        return $this->belongsTo(ParentBusiness::class);
    }

    public function displaySign(): string
    {
        return in_array($this->entry_type, ['purchase_reservation', 'purchase_capture'], true) ? '-' : '+';
    }

    public function displayColor(): string
    {
        return $this->displaySign() === '-' ? 'text-rose-700' : 'text-emerald-700';
    }
}
