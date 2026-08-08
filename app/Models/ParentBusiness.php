<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentBusiness extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function admins()
    {
        return $this->hasMany(ParentAdmin::class);
    }

    public function providerConnections()
    {
        return $this->hasMany(ProviderConnection::class);
    }

    public function affiliates()
    {
        return $this->hasMany(Affiliate::class);
    }

    public function productPlans()
    {
        return $this->hasMany(ProductPlan::class);
    }
}
