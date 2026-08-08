<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffiliateUserPlan extends AffiliateScopedModel
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (AffiliateUserPlan $plan): void {
            if ($plan->exists && ! $plan->isDirty('plan_level')) {
                return;
            }

            $level = filter_var($plan->plan_level, FILTER_VALIDATE_INT);
            $plan->canonical_plan_level = $level !== false && $level >= 1 && $level <= 6 ? $level : null;
        });
    }

    public function users()
    {
        return $this->hasMany(User::class, 'user_plan_id');
    }
}
