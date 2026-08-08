<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Affiliate extends Model
{
    // on creating an ffiliate, something like the plans, categories and even product plans should be created or a checklist that ensures all those are created
    use HasFactory;

    protected $guarded = [];

    public function site_colors()
    {
        return $this->hasMany(AdminColorSetting::class, 'id', 'affiliate_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function parentBusiness(): BelongsTo
    {
        return $this->belongsTo(ParentBusiness::class);
    }

    public function parentResellerLevel(): BelongsTo
    {
        return $this->belongsTo(ParentResellerLevel::class);
    }
}
