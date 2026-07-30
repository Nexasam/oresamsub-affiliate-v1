<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffiliateUserPlan extends AffiliateScopedModel
{
    use HasFactory;
    protected $guarded = [];

    public function users()
    {
        return $this->hasMany(User::class, 'user_plan_id');
    }
}
