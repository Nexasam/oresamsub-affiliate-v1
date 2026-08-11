<?php

use App\Services\Funding\FundingChargeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('calculates flat and capped percentage funding charges exactly', function () {
    $calculator = app(FundingChargeCalculator::class);

    expect($calculator->calculate('flat', '50.00', null, '10000.00'))->toBe('50.00')
        ->and($calculator->calculate('percentage', '1.50', '100.00', '5000.00'))->toBe('75.00')
        ->and($calculator->calculate('percentage', '1.50', '100.00', '10000.00'))->toBe('100.00');
});

it('rejects invalid bank charge configurations', function (string $type, string $value, ?string $cap) {
    expect(fn () => app(FundingChargeCalculator::class)->validate($type, $value, $cap))->toThrow(ValidationException::class);
})->with([
    ['unknown', '1', null],
    ['flat', '-1', null],
    ['percentage', '101', '100'],
    ['percentage', '1', null],
]);
