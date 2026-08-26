<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderAdapter extends Model
{
    protected $guarded = [];
    protected $appends = ['adapter'];

    protected function casts(): array
    {
        return ['capabilities' => 'array', 'settings' => 'array', 'version' => 'integer'];
    }

    public function connections(): HasMany
    {
        return $this->hasMany(ProviderConnection::class);
    }

    public function parentConnections(): HasMany
    {
        return $this->hasMany(ParentProviderConnection::class);
    }

    public function getAdapterAttribute(): string
    {
        return (string) $this->adapter_key;
    }
}
