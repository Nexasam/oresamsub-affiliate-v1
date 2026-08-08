<?php

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\AffiliateServiceProfitCap;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ParentResellerLevel;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Services\ParentAdmin\AffiliateProfitCapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function capContext(string $slug = 'cap-parent'): array
{
    $parent = ParentBusiness::create(['name' => ucfirst($slug), 'slug' => $slug]);
    $level = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Basic', 'position' => 1]);
    $admin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Cap Owner', 'email' => "{$slug}@example.test", 'password' => 'secret-password', 'active' => true]);
    $affiliate = Affiliate::create([
        'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id,
        'name' => ucfirst($slug).' Affiliate', 'slug' => $slug.'-affiliate', 'affiliate_plan_id' => 1,
        'ip_address' => '10.20.'.random_int(1, 200).'.'.random_int(1, 200), 'contact_phone' => '080'.random_int(10000000, 99999999),
        'contact_email' => "affiliate-{$slug}@example.test", 'parent_key' => "key-{$slug}", 'parent_email' => "parent-{$slug}@example.test",
    ]);

    return [$parent, $admin, $affiliate];
}

function capProducts(): array
{
    return collect(['Data', 'Cable', 'Airtime', 'Electricity'])->mapWithKeys(function ($name) {
        $slug = strtolower($name).'-cap-product';

        return [$name => Product::create(['api_id' => $slug, 'product_name' => $name, 'slug' => $slug])];
    })->all();
}

it('stores one parent-owned service cap per affiliate product and customer level', function () {
    expect(Schema::hasColumns('affiliate_service_profit_caps', [
        'parent_business_id', 'affiliate_id', 'product_id', 'customer_level', 'calculation_type', 'max_value',
    ]))->toBeTrue();

    [$parent, , $affiliate] = capContext();
    $product = capProducts()['Data'];
    $cap = AffiliateServiceProfitCap::create([
        'parent_business_id' => $parent->id, 'affiliate_id' => $affiliate->id, 'product_id' => $product->id,
        'customer_level' => 1, 'calculation_type' => 'flat', 'max_value' => 70,
    ]);

    expect($cap->max_value)->toBe('70.00')
        ->and($cap->affiliate->is($affiliate))->toBeTrue()
        ->and($affiliate->serviceProfitCaps)->toHaveCount(1);
});

it('creates 70 naira and one percent defaults for all six levels', function () {
    [, , $affiliate] = capContext('default-caps');
    capProducts();

    $caps = app(AffiliateProfitCapService::class)->ensureCaps($affiliate);

    expect($caps)->toHaveCount(24)
        ->and($caps->where('calculation_type', 'flat')->pluck('max_value')->unique()->values()->all())->toBe(['70.00'])
        ->and($caps->where('calculation_type', 'percent')->pluck('max_value')->unique()->values()->all())->toBe(['1.00']);
});

it('lets a parent admin manage only its affiliates complete cap matrix', function () {
    [$parent, $admin, $affiliate] = capContext('api-caps');
    capProducts();
    [, , $foreignAffiliate] = capContext('foreign-caps');

    $response = $this->actingAs($admin, 'parent_admin')->getJson("/parent-admin/pricing/affiliates/{$affiliate->id}/caps")
        ->assertOk()->assertJsonCount(24, 'caps');
    $caps = collect($response->json('caps'))->map(function ($cap) {
        if ($cap['customer_level'] === 1 && $cap['calculation_type'] === 'flat') {
            $cap['max_value'] = 60;
        }

        return collect($cap)->only(['product_id', 'customer_level', 'calculation_type', 'max_value'])->all();
    })->all();

    $this->actingAs($admin, 'parent_admin')->putJson("/parent-admin/pricing/affiliates/{$affiliate->id}/caps", ['caps' => $caps])
        ->assertOk();
    $this->actingAs($admin, 'parent_admin')->getJson("/parent-admin/pricing/affiliates/{$foreignAffiliate->id}/caps")
        ->assertNotFound();

    expect(AffiliateServiceProfitCap::where('parent_business_id', $parent->id)->where('max_value', 60)->exists())->toBeTrue();
});

it('rejects a cap reduction below existing profits and reports every violation', function () {
    [, $admin, $affiliate] = capContext('conflict-caps');
    $data = capProducts()['Data'];
    $category = ProductPlanCategory::create(['api_id' => 'conflict-category', 'product_id' => $data->id, 'product_plan_category_name' => 'SME']);
    $plan = ProductPlan::create(['parent_business_id' => $affiliate->parent_business_id, 'product_plan_category_id' => $category->id, 'product_plan_name' => 'Conflict 1GB']);
    AffiliateProductPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'product_plan_id' => $plan->id, 'product_plan_name' => 'Conflict 1GB',
        'user_level_1_profit' => 80, 'user_level_2_profit' => 90,
    ]);
    $service = app(AffiliateProfitCapService::class);
    $caps = $service->ensureCaps($affiliate)->map(fn ($cap) => [
        'product_id' => $cap->product_id, 'customer_level' => $cap->customer_level,
        'calculation_type' => $cap->calculation_type, 'max_value' => $cap->product_id === $data->id ? 70 : $cap->max_value,
    ])->all();

    $this->actingAs($admin, 'parent_admin')->putJson("/parent-admin/pricing/affiliates/{$affiliate->id}/caps", ['caps' => $caps])
        ->assertUnprocessable()->assertJsonCount(2, 'violations')
        ->assertJsonPath('violations.0.plan_name', 'Conflict 1GB');

    expect(AffiliateServiceProfitCap::where('affiliate_id', $affiliate->id)->where('product_id', $data->id)->where('customer_level', 1)->value('max_value'))->toBe('80.00');
});

it('enforces an affiliates service cap whenever plan margins are changed', function () {
    [, , $affiliate] = capContext('enforced-caps');
    $data = capProducts()['Data'];
    $category = ProductPlanCategory::create(['api_id' => 'enforced-category', 'product_id' => $data->id, 'product_plan_category_name' => 'SME']);
    $plan = ProductPlan::create(['parent_business_id' => $affiliate->parent_business_id, 'product_plan_category_id' => $category->id, 'product_plan_name' => 'Enforced 1GB']);
    $affiliatePlan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'product_plan_id' => $plan->id,
        'product_plan_name' => 'Enforced 1GB', 'user_level_1_profit' => 50,
    ]);
    app(AffiliateProfitCapService::class)->ensureCaps($affiliate);

    $affiliatePlan->update(['user_level_1_profit' => 70]);
    expect(fn () => $affiliatePlan->update(['user_level_1_profit' => 71]))
        ->toThrow(ValidationException::class);
    expect((float) $affiliatePlan->fresh()->user_level_1_profit)->toBe(70.0);
});

it('renders affiliate maximum pricing inside the parent pricing workspace', function () {
    [, $admin] = capContext('cap-workspace');

    $this->actingAs($admin, 'parent_admin')->get('/parent-admin/pricing')
        ->assertOk()->assertSee('Affiliate maximum pricing')
        ->assertSee('Select affiliate')->assertSee('Customer levels 1–6')
        ->assertSee('Save affiliate maximums');
});
