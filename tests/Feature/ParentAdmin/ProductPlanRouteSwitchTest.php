<?php

use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Models\ProviderConnection;
use App\Models\ProductPlanRouteSwitch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function routeSwitchFixture(): array
{
    $parent = ParentBusiness::create(['name' => 'Route Parent', 'slug' => 'route-parent']);
    $admin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => 'route-owner@example.test', 'password' => 'password123', 'active' => true]);
    $product = Product::create(['api_id' => 'route-data', 'product_name' => 'Data', 'slug' => 'data']);
    $category = ProductPlanCategory::create(['api_id' => 'route-mtn', 'product_id' => $product->id, 'product_plan_category_name' => 'MTN']);
    $plan = ProductPlan::create(['parent_business_id' => $parent->id, 'product_plan_category_id' => $category->id, 'product_plan_name' => 'MTN 1GB', 'cost_price' => 500, 'visibility' => true, 'affiliate_visibility' => true]);

    $sharedOne = ProviderConnection::create(['name' => 'Oresam', 'slug' => 'route-oresam', 'adapter' => 'http', 'capabilities' => ['services' => ['data']], 'status' => 'active']);
    $sharedTwo = ProviderConnection::create(['name' => 'Gongoz', 'slug' => 'route-gongoz', 'adapter' => 'http', 'capabilities' => ['services' => ['data']], 'status' => 'active']);
    $first = ParentProviderConnection::create(['parent_business_id' => $parent->id, 'provider_connection_id' => $sharedOne->id, 'name' => 'Oresam', 'status' => 'active', 'approval_status' => 'approved']);
    $second = ParentProviderConnection::create(['parent_business_id' => $parent->id, 'provider_connection_id' => $sharedTwo->id, 'name' => 'Gongoz', 'status' => 'active', 'approval_status' => 'approved']);
    $firstRoute = $plan->providerRoutes()->create(['parent_business_id' => $parent->id, 'parent_provider_connection_id' => $first->id, 'provider_plan_id' => '292', 'priority' => 1, 'active' => true]);
    $secondRoute = $plan->providerRoutes()->create(['parent_business_id' => $parent->id, 'parent_provider_connection_id' => $second->id, 'provider_plan_id' => '389', 'priority' => 2, 'active' => false]);

    return compact('parent', 'admin', 'plan', 'first', 'second', 'firstRoute', 'secondRoute');
}

it('switches to a remembered provider mapping and preserves the previous mapping', function () {
    $f = routeSwitchFixture();

    $this->actingAs($f['admin'], 'parent_admin')->patch(route('parent-admin.product-plans.routes.switch', $f['plan']), [
        'parent_provider_connection_id' => $f['second']->id,
        'provider_plan_id' => '389',
    ])->assertRedirect(route('parent-admin.dashboard'));

    expect($f['secondRoute']->fresh()->priority)->toBe(1)
        ->and($f['secondRoute']->fresh()->active)->toBeTrue()
        ->and($f['firstRoute']->fresh()->priority)->toBeGreaterThan(1)
        ->and($f['firstRoute']->fresh()->active)->toBeFalse()
        ->and($f['firstRoute']->fresh()->provider_plan_id)->toBe('292');

    $history = ProductPlanRouteSwitch::query()->sole();
    expect($history->product_plan_id)->toBe($f['plan']->id)
        ->and($history->from_parent_provider_connection_id)->toBe($f['first']->id)
        ->and($history->to_parent_provider_connection_id)->toBe($f['second']->id)
        ->and($history->provider_plan_id)->toBe('389');
});

it('bulk switches selected plans atomically and reuses remembered provider ids', function () {
    $f = routeSwitchFixture();
    $secondPlan = ProductPlan::create([
        'parent_business_id' => $f['parent']->id,
        'product_plan_category_id' => $f['plan']->product_plan_category_id,
        'product_plan_name' => 'MTN 2GB',
        'cost_price' => 900,
        'visibility' => true,
        'affiliate_visibility' => true,
    ]);
    $secondPlan->providerRoutes()->create([
        'parent_business_id' => $f['parent']->id,
        'parent_provider_connection_id' => $f['first']->id,
        'provider_plan_id' => '293',
        'priority' => 1,
        'active' => true,
    ]);
    $remembered = $secondPlan->providerRoutes()->create([
        'parent_business_id' => $f['parent']->id,
        'parent_provider_connection_id' => $f['second']->id,
        'provider_plan_id' => '390',
        'priority' => 2,
        'active' => false,
    ]);

    $this->actingAs($f['admin'], 'parent_admin')->patch(route('parent-admin.product-plans.routes.bulk-switch'), [
        'parent_provider_connection_id' => $f['second']->id,
        'plans' => [
            ['product_plan_id' => $f['plan']->id, 'provider_plan_id' => '389'],
            ['product_plan_id' => $secondPlan->id, 'provider_plan_id' => ''],
        ],
    ])->assertRedirect(route('parent-admin.product-plans.index'));

    expect($f['secondRoute']->fresh()->priority)->toBe(1)
        ->and($remembered->fresh()->priority)->toBe(1)
        ->and(ProductPlanRouteSwitch::query()->count())->toBe(2);
});

it('rejects the whole bulk connection switch when one plan mapping is missing', function () {
    $f = routeSwitchFixture();
    $unmappedPlan = ProductPlan::create([
        'parent_business_id' => $f['parent']->id,
        'product_plan_category_id' => $f['plan']->product_plan_category_id,
        'product_plan_name' => 'MTN 3GB',
        'cost_price' => 1200,
    ]);

    $this->actingAs($f['admin'], 'parent_admin')->from(route('parent-admin.product-plans.index'))
        ->patch(route('parent-admin.product-plans.routes.bulk-switch'), [
            'parent_provider_connection_id' => $f['second']->id,
            'plans' => [
                ['product_plan_id' => $f['plan']->id, 'provider_plan_id' => '389'],
                ['product_plan_id' => $unmappedPlan->id, 'provider_plan_id' => ''],
            ],
        ])->assertRedirect(route('parent-admin.product-plans.index'))
        ->assertSessionHasErrors('plans.1.provider_plan_id');

    expect($f['firstRoute']->fresh()->priority)->toBe(1)
        ->and(ProductPlanRouteSwitch::query()->count())->toBe(0);
});

it('requires a provider plan id on first mapping and rejects another parents connection', function () {
    $f = routeSwitchFixture();
    $thirdShared = ProviderConnection::create(['name' => 'Third', 'slug' => 'route-third', 'adapter' => 'http', 'capabilities' => ['services' => ['data']], 'status' => 'active']);
    $third = ParentProviderConnection::create(['parent_business_id' => $f['parent']->id, 'provider_connection_id' => $thirdShared->id, 'name' => 'Third', 'status' => 'active', 'approval_status' => 'approved']);

    $this->actingAs($f['admin'], 'parent_admin')->from(route('parent-admin.dashboard'))
        ->patch(route('parent-admin.product-plans.routes.switch', $f['plan']), ['parent_provider_connection_id' => $third->id])
        ->assertSessionHasErrors('provider_plan_id');

    $other = ParentBusiness::create(['name' => 'Other', 'slug' => 'route-other']);
    $foreign = ParentProviderConnection::create(['parent_business_id' => $other->id, 'provider_connection_id' => $thirdShared->id, 'name' => 'Foreign', 'status' => 'active', 'approval_status' => 'approved']);
    $this->actingAs($f['admin'], 'parent_admin')->patch(route('parent-admin.product-plans.routes.switch', $f['plan']), [
        'parent_provider_connection_id' => $foreign->id, 'provider_plan_id' => 'BAD',
    ])->assertNotFound();
});
