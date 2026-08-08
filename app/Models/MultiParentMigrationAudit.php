<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MultiParentMigrationAudit extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
