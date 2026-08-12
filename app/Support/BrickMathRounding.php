<?php

namespace App\Support;

use Brick\Math\RoundingMode;

final class BrickMathRounding
{
    public static function halfUp(): mixed
    {
        $modern = RoundingMode::class.'::HalfUp';

        return defined($modern)
            ? constant($modern)
            : constant(RoundingMode::class.'::HALF_UP');
    }
}
