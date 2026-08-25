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

    public function displayLabel(): string
    {
        return match ($this->entry_type) {
            'purchase_reservation' => 'Purchase reservation',
            'purchase_capture' => 'Purchase capture',
            'reservation_release' => 'Reservation release',
            'refund' => 'Purchase refund',
            'settlement_funding' => 'Settlement funding',
            'manual_credit' => 'Manual credit',
            default => str($this->entry_type)->replace('_', ' ')->title()->toString(),
        };
    }

    public function displayMethod(): string
    {
        return match ($this->entry_type) {
            'settlement_funding' => (string) data_get($this->metadata, 'provider_name', 'Virtual account'),
            'manual_credit' => (string) data_get($this->metadata, 'method', 'Parent verified credit'),
            default => (string) data_get($this->metadata, 'method', 'Parent-managed purchase'),
        };
    }

    public function purchaseReference(): string
    {
        return (string) data_get($this->metadata, 'purchase_reference', preg_replace('/:(reserve|capture|release|refund)$/', '', $this->reference));
    }

    public function displayService(): ?string
    {
        $service = data_get($this->metadata, 'service');
        if ($service) return str((string) $service)->replace('_', ' ')->title()->toString();

        if (! in_array($this->entry_type, ['purchase_reservation', 'purchase_capture', 'reservation_release', 'refund'], true)) return null;

        return str($this->purchaseReference())->before('_')->replace('-', ' ')->title()->toString();
    }
}
