<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPlanRouteSwitch extends Model
{
    protected $guarded = [];

    public function fromConnection(): BelongsTo
    {
        return $this->belongsTo(ParentProviderConnection::class, 'from_parent_provider_connection_id');
    }

    public function toConnection(): BelongsTo
    {
        return $this->belongsTo(ParentProviderConnection::class, 'to_parent_provider_connection_id');
    }

    public function parentAdmin(): BelongsTo
    {
        return $this->belongsTo(ParentAdmin::class);
    }
}
