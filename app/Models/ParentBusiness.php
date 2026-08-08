<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentBusiness extends Model
{
    protected $guarded = [];

    public function providerConnections(): HasMany
    {
        return $this->hasMany(ParentProviderConnection::class);
    }

    public function resellerLevels(): HasMany
    {
        return $this->hasMany(ParentResellerLevel::class);
    }
}
