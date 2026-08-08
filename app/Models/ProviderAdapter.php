<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderAdapter extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function connections()
    {
        return $this->hasMany(ProviderConnection::class);
    }
}
