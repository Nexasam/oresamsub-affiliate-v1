<?php

use App\Support\BrickMathRounding;
use Brick\Math\RoundingMode;

it('resolves the installed brick math half-up rounding mode', function () {
    $expected = defined(RoundingMode::class.'::HalfUp')
        ? constant(RoundingMode::class.'::HalfUp')
        : constant(RoundingMode::class.'::HALF_UP');

    expect(BrickMathRounding::halfUp())->toBe($expected);
});
