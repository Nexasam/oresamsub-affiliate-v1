<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderConnection extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
        ];
    }

    public function parentConnections(): HasMany
    {
        return $this->hasMany(ParentProviderConnection::class);
    }
}
