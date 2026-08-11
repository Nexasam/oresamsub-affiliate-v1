<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPlanProviderRoute extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function parentProviderConnection(): BelongsTo
    {
        return $this->belongsTo(ParentProviderConnection::class);
    }
}
