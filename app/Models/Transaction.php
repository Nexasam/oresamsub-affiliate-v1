<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends AffiliateScopedModel
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        // 'created_at' => 'datetime:Africa/Lagos',
        'retry_count' => 'integer',
        'provider_cost_snapshot' => 'decimal:2',
        'parent_cost_snapshot' => 'decimal:2',
        'affiliate_cost_snapshot' => 'decimal:2',
        'customer_price_snapshot' => 'decimal:2',
        'parent_profit_snapshot' => 'decimal:2',
        'affiliate_profit_snapshot' => 'decimal:2',
        'provider_response' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function parentBusiness(): BelongsTo
    {
        return $this->belongsTo(ParentBusiness::class);
    }

    public function parentProviderConnection(): BelongsTo
    {
        return $this->belongsTo(ParentProviderConnection::class);
    }

    public function productPlanProviderRoute(): BelongsTo
    {
        return $this->belongsTo(ProductPlanProviderRoute::class);
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
