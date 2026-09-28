<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Affiliate extends Model
{
    // on creating an ffiliate, something like the plans, categories and even product plans should be created or a checklist that ensures all those are created
    use HasFactory;

    protected $guarded = [];

    public function site_colors()
    {
        return $this->hasMany(AdminColorSetting::class, 'id', 'affiliate_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function parentBusiness(): BelongsTo
    {
        return $this->belongsTo(ParentBusiness::class);
    }

    public function parentResellerLevel(): BelongsTo
    {
        return $this->belongsTo(ParentResellerLevel::class);
    }

    public function serviceProfitCaps(): HasMany
    {
        return $this->hasMany(AffiliateServiceProfitCap::class);
    }

    public function fundingProviderConfigurations(): HasMany
    {
        return $this->hasMany(AffiliateFundingProviderConfig::class);
    }

    public function settlementWallet(): HasOne
    {
        return $this->hasOne(AffiliateSettlementWallet::class);
    }

    public function settlementVirtualAccounts(): HasMany
    {
        return $this->hasMany(AffiliateSettlementVirtualAccount::class);
    }

    public function processingProfile(): HasOne
    {
        return $this->hasOne(AffiliateProcessingProfile::class);
    }

    public function managesOwnPurchaseCredentials(): bool
    {
        return $this->processingProfile?->management_mode !== 'parent_managed';
    }

    public function usesLegacyAdminSettings(): bool
    {
        return $this->processingProfile?->management_mode !== 'parent_managed';
    }

    public function transactionReferenceSignature(): string
    {
        return Str::slug((string) ($this->slug ?: $this->name), '-');
    }

    public static function transactionReferenceSignatureForUser(int|string|null $userId): ?string
    {
        if (! $userId) {
            return static::transactionReferenceSignatureFromSession();
        }

        $affiliate = static::query()
            ->select('affiliates.id', 'affiliates.name', 'affiliates.slug')
            ->join('users', 'users.affiliate_id', '=', 'affiliates.id')
            ->where('users.id', $userId)
            ->first();

        return $affiliate?->transactionReferenceSignature()
            ?: static::transactionReferenceSignatureFromSession();
    }

    private static function transactionReferenceSignatureFromSession(): ?string
    {
        $affiliate = session('affiliate');

        if (! $affiliate) {
            return null;
        }

        if ($affiliate instanceof self) {
            return $affiliate->transactionReferenceSignature();
        }

        $value = data_get($affiliate, 'slug') ?: data_get($affiliate, 'name');

        return $value ? Str::slug((string) $value, '-') : null;
    }
}
