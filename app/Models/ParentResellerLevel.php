<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ParentResellerLevel extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (ParentResellerLevel $level) {
            if ($level->position < 1 || $level->position > 6) {
                throw new InvalidArgumentException('Reseller level position must be between 1 and 6.');
            }
        });
    }
}
