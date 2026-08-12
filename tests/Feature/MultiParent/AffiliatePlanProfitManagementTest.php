<?php

use App\Models\AffiliateServiceProfitCap;
use App\Models\ProductPlanParentPrice;
use App\Services\Pricing\AffiliatePlanProfitService;
use Illuminate\Validation\ValidationException;

it('updates all six customer margins within normalized parent caps', function () {
    $f = affiliateProfitFixture('editor1');
    foreach (range(1, 6) as $level) {
        AffiliateServiceProfitCap::create(['parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id, 'product_id' => $f['product']->id, 'customer_level' => $level, 'calculation_type' => 'flat', 'max_value' => 70]);
    }

    app(AffiliatePlanProfitService::class)->update($f['affiliate'], $f['plan']->id, array_fill_keys(range(1, 6), 60));

    expect((float) $f['affiliatePlan']->fresh()->user_level_1_profit)->toBe(60.0)
        ->and((float) $f['affiliatePlan']->fresh()->user_level_6_profit)->toBe(60.0);
});

it('uses the stricter plan level max profit override', function () {
    $f = affiliateProfitFixture('editor2');
    foreach (range(1, 6) as $level) {
        AffiliateServiceProfitCap::create(['parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id, 'product_id' => $f['product']->id, 'customer_level' => $level, 'calculation_type' => 'flat', 'max_value' => 70]);
    }
    ProductPlanParentPrice::create(['parent_business_id' => $f['parent']->id, 'product_plan_id' => $f['plan']->id, 'parent_reseller_level_id' => $f['level']->id, 'selling_price' => 120, 'max_profit' => 50]);

    expect(fn () => app(AffiliatePlanProfitService::class)->update($f['affiliate'], $f['plan']->id, [1 => 60, 2 => 40, 3 => 40, 4 => 40, 5 => 40, 6 => 40]))
        ->toThrow(ValidationException::class);
});

it('rejects a plan owned by another affiliate or parent', function () {
    $first = affiliateProfitFixture('editor3');
    $other = affiliateProfitFixture('editor4');

    expect(fn () => app(AffiliatePlanProfitService::class)->update($first['affiliate'], $other['plan']->id, array_fill_keys(range(1, 6), 1)))
        ->toThrow(ValidationException::class);
});
