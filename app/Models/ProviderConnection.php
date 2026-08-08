<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderConnection extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'settings' => 'array',
            'last_tested_at' => 'datetime',
        ];
    }

    public function parentBusiness()
    {
        return $this->belongsTo(ParentBusiness::class);
    }

    public function adapter()
    {
        return $this->belongsTo(ProviderAdapter::class, 'provider_adapter_id');
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
