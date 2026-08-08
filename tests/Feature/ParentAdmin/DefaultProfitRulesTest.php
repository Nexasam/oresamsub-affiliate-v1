<?php

use App\Models\ParentBusiness;
use App\Models\ParentDefaultProfitRule;
use App\Models\ParentResellerLevel;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('stores one typed default profit rule per parent level and product', function () {
    expect(Schema::hasColumns('parent_default_profit_rules', [
        'parent_business_id', 'parent_reseller_level_id', 'product_id', 'calculation_type', 'value',
    ]))->toBeTrue();

    $parent = ParentBusiness::create(['name' => 'Rules Parent', 'slug' => 'rules-parent']);
    $level = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Basic', 'position' => 1]);
    $product = Product::create(['api_id' => 'rules-data', 'product_name' => 'Data', 'slug' => 'rules-data']);
    $rule = ParentDefaultProfitRule::create([
        'parent_business_id' => $parent->id,
        'parent_reseller_level_id' => $level->id,
        'product_id' => $product->id,
        'calculation_type' => 'flat',
        'value' => 50,
    ]);

    expect($rule->value)->toBe('50.00')
        ->and($rule->parentBusiness->is($parent))->toBeTrue()
        ->and($rule->parentResellerLevel->is($level))->toBeTrue()
        ->and($rule->product->is($product))->toBeTrue()
        ->and($parent->defaultProfitRules)->toHaveCount(1)
        ->and($level->defaultProfitRules)->toHaveCount(1);

    expect(fn () => ParentDefaultProfitRule::create([
        'parent_business_id' => $parent->id,
        'parent_reseller_level_id' => $level->id,
        'product_id' => $product->id,
        'calculation_type' => 'flat',
        'value' => 60,
    ]))->toThrow(QueryException::class);
});
