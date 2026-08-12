<?php

namespace App\Services\Funding;

use App\Support\BrickMathRounding;
use Brick\Math\BigDecimal;
use Illuminate\Validation\ValidationException;

class FundingChargeCalculator
{
    public function validate(string $type, string $value, ?string $cap): void
    {
        if (! in_array($type, ['flat', 'percentage'], true)) {
            $this->fail('Charge type must be flat or percentage.');
        }

        $rate = BigDecimal::of($value);
        if ($rate->isNegative() || ($type === 'percentage' && $rate->isGreaterThan(100))) {
            $this->fail('The funding charge value is invalid.');
        }

        if ($type === 'percentage' && ($cap === null || BigDecimal::of($cap)->isNegative())) {
            $this->fail('A non-negative percentage cap is required.');
        }
    }

    public function calculate(string $type, string $value, ?string $cap, string $amount): string
    {
        $this->validate($type, $value, $cap);
        $charge = $type === 'flat'
            ? BigDecimal::of($value)
            : BigDecimal::of($amount)->multipliedBy($value)->dividedBy(100, 2, BrickMathRounding::halfUp());

        if ($type === 'percentage' && $charge->isGreaterThan(BigDecimal::of($cap))) {
            $charge = BigDecimal::of($cap);
        }

        return (string) $charge->toScale(2, BrickMathRounding::halfUp());
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['rate_value' => $message]);
    }
}
