<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        ];
    }
}
