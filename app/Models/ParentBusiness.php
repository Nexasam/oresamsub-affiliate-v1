<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentBusiness extends Model
{
    protected $guarded = [];

    public function providerConnections(): HasMany
    {
        return $this->hasMany(ParentProviderConnection::class);
    }

    public function parentAdmins(): HasMany
    {
        return $this->hasMany(ParentAdmin::class);
    }

    public function affiliates(): HasMany
    {
        return $this->hasMany(Affiliate::class);
    }

    public function resellerLevels(): HasMany
    {
        return $this->hasMany(ParentResellerLevel::class);
    }

    public function productPlans(): HasMany
    {
        return $this->hasMany(ProductPlan::class);
    }

    public function defaultProfitRules(): HasMany
    {
        return $this->hasMany(ParentDefaultProfitRule::class);
    }

    public function affiliateServiceProfitCaps(): HasMany
    {
        return $this->hasMany(AffiliateServiceProfitCap::class);
    }

    public function fundingProviders(): HasMany
    {
        return $this->hasMany(ParentFundingProvider::class);
    }
}
