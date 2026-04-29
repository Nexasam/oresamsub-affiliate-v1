<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasAffiliateScope
{
    protected static function bootHasAffiliateScope()
    {
        // Auto-set affiliate_id when creating
        static::creating(function ($model) {
            if (session()->has('affiliate') && ! $model->affiliate_id) {
                $model->affiliate_id = session('affiliate')->id ?? null;
            }
        });

        // Global scope
        static::addGlobalScope('affiliate', function (Builder $builder) {
            if (session()->has('affiliate')) {
                $builder->where('affiliate_id', session('affiliate')->id);
            }
        });
    }
}
