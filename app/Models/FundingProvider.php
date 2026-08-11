<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FundingProvider extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['credential_fields' => 'array', 'settings' => 'array', 'active' => 'boolean'];
    }

    public function parentConfigurations(): HasMany
    {
        return $this->hasMany(ParentFundingProvider::class);
    }
}
