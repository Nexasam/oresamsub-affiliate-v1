<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateLicense extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'purchase_amount' => 'decimal:2',
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function parentBusiness()
    {
        return $this->belongsTo(ParentBusiness::class);
    }
}
