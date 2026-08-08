<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPlanParentPrice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'max_profit' => 'decimal:2',
        ];
    }
}
