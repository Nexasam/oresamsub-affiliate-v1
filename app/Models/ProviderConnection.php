<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderConnection extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'settings' => 'array',
            'adapter_version' => 'integer',
        ];
    }

    public function providerAdapter(): BelongsTo
    {
        return $this->belongsTo(ProviderAdapter::class);
    }

    public function parentConnections(): HasMany
    {
        return $this->hasMany(ParentProviderConnection::class);
    }
}
