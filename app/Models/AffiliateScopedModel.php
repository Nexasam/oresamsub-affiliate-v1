<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AffiliateScopedModel extends Model
{
    protected static function booted()
    {
        // Auto-set affiliate_id when creating
        static::creating(function ($model): void {
            if (session()->has('affiliate') && ! $model->affiliate_id) {
                $model->affiliate_id = session('affiliate')->id ?? null;
            }
        });

        // Apply global scope
        static::addGlobalScope('affiliate', function (Builder $builder) {
            if (session()->has('affiliate')) {
                $builder->where('affiliate_id', session('affiliate')->id);
            }
        });
    }

    /**
     * Query without affiliate scope
     */
    public static function allAffiliates()
    {
        return static::withoutGlobalScope('affiliate');
    }
}
