<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentProviderConnection extends Model
{
    protected $guarded = [];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'settings' => 'array',
            'last_tested_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function parentBusiness(): BelongsTo
    {
        return $this->belongsTo(ParentBusiness::class);
    }

    public function providerConnection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }
}
