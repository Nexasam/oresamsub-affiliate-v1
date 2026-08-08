<?php

use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ParentDefaultProfitRule;
use App\Models\ParentResellerLevel;
use App\Models\Product;
use App\Services\ParentAdmin\ParentProfitRuleService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

it('idempotently creates the four initial defaults for every active reseller level', function () {
    $parent = ParentBusiness::create(['name' => 'Default Parent', 'slug' => 'default-parent']);
    ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Basic', 'position' => 1]);
    ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Gold', 'position' => 2]);
    foreach (['Data', 'Cable Subscription', 'Airtime', 'Electricity Bill'] as $index => $name) {
        Product::create(['api_id' => 'default-'.$index, 'product_name' => $name, 'slug' => 'default-'.$index]);
    }

    $service = app(ParentProfitRuleService::class);
    $service->ensureDefaults($parent);
    $rules = $service->ensureDefaults($parent);

    expect($rules)->toHaveCount(8)
        ->and($rules->where('calculation_type', 'flat'))->toHaveCount(4)
        ->and($rules->where('calculation_type', 'percent_discount'))->toHaveCount(4)
        ->and($rules->where('calculation_type', 'flat')->pluck('value')->unique()->all())->toBe(['50.00'])
        ->and($rules->where('calculation_type', 'percent_discount')->pluck('value')->unique()->all())->toBe(['1.00']);
});

it('allows a parent admin to update only its own default rules', function () {
    $parent = ParentBusiness::create(['name' => 'API Parent', 'slug' => 'api-parent']);
    $admin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => 'rules@example.test', 'password' => 'secret-password', 'active' => true]);
    $level = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Basic', 'position' => 1]);
    $data = Product::create(['api_id' => 'api-data', 'product_name' => 'Data', 'slug' => 'api-data']);

    $this->actingAs($admin, 'parent_admin')->getJson('/parent-admin/pricing/data')
        ->assertOk()->assertJsonPath('defaults.0.value', '50.00');

    $this->actingAs($admin, 'parent_admin')->putJson('/parent-admin/pricing/defaults', ['rules' => [[
        'parent_reseller_level_id' => $level->id,
        'product_id' => $data->id,
        'calculation_type' => 'flat',
        'value' => 35,
    ]]])->assertOk()->assertJsonPath('defaults.0.value', '35.00');
});
