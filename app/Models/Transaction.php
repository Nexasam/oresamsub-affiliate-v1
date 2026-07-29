<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends AffiliateScopedModel
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        // 'created_at' => 'datetime:Africa/Lagos',
        'retry_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function manual_processing_locker()
    {
        return $this->belongsTo(User::class, 'locked_for_manual_processing', 'id');
    }

    public function product_plan()
    {
        return $this->belongsTo(AffiliateProductPlan::class, 'affiliate_product_plan_id', 'id');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->timezone('Africa/Lagos')->toIso8601String();
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->timezone('Africa/Lagos')->toIso8601String();
    }
}
