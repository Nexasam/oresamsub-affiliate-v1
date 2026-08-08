<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;

class AffiliateUserPlan extends AffiliateScopedModel
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (AffiliateUserPlan $plan): void {
            $level = filter_var($plan->plan_level, FILTER_VALIDATE_INT);
            if ($plan->exists
                && $plan->canonical_plan_level === null
                && $level !== false && $level >= 1 && $level <= 6
                && $plan->isDirty('visibility') && (int) $plan->visibility === 1) {
                throw ValidationException::withMessages([
                    'visibility' => 'A retained duplicate customer plan cannot be reactivated.',
                ]);
            }

            if ($plan->exists && ! $plan->isDirty('plan_level')) {
                return;
            }

            $plan->canonical_plan_level = $level !== false && $level >= 1 && $level <= 6 ? $level : null;
        });
    }

    public function users()
    {
        return $this->hasMany(User::class, 'user_plan_id');
    }
}
