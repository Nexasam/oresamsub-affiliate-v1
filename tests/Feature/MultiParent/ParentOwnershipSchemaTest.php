<?php

use App\Models\MultiParentMigrationAudit;
use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\ParentResellerLevel;
use App\Models\ProductPlan;
use App\Models\ProductPlanParentPrice;
use App\Models\ProductPlanProviderRoute;
use App\Models\ProviderConnection;
use App\Models\Transaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function createOwnershipTestPlan(ParentBusiness $parent, string $suffix): ProductPlan
{
    $productId = DB::table('products')->insertGetId([
        'api_id' => "product-{$suffix}",
        'slug' => "product-{$suffix}",
        'product_name' => "Product {$suffix}",
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $categoryId = DB::table('product_plan_categories')->insertGetId([
        'api_id' => "category-{$suffix}",
        'product_plan_category_name' => "Category {$suffix}",
        'product_id' => $productId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return ProductPlan::create([
        'product_plan_name' => "Plan {$suffix}",
        'product_plan_category_id' => $categoryId,
        'parent_business_id' => $parent->id,
    ]);
}

it('creates parent ownership routing and snapshot schema', function () {
    expect(Schema::hasColumns('affiliates', ['parent_business_id', 'parent_reseller_level_id']))->toBeTrue()
        ->and(Schema::hasColumn('product_plans', 'parent_business_id'))->toBeTrue()
        ->and(Schema::hasColumns('transactions', [
            'parent_business_id', 'parent_provider_connection_id', 'product_plan_provider_route_id',
            'provider_plan_id_snapshot', 'provider_reference', 'routing_status',
            'provider_cost_snapshot', 'parent_cost_snapshot', 'affiliate_cost_snapshot',
            'customer_price_snapshot', 'parent_profit_snapshot', 'affiliate_profit_snapshot',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('product_plan_parent_prices', [
            'parent_business_id', 'product_plan_id', 'parent_reseller_level_id', 'selling_price', 'max_profit',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('product_plan_provider_routes', [
            'parent_business_id', 'product_plan_id', 'parent_provider_connection_id',
            'provider_plan_id', 'priority', 'active',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('multi_parent_migration_audits', [
            'batch_uuid', 'action', 'entity_type', 'entity_id', 'from_value', 'to_value', 'metadata',
        ]))->toBeTrue();
});

it('enforces parent-owned prices and their uniqueness', function () {
    $firstParent = ParentBusiness::create(['name' => 'First', 'slug' => 'first']);
    $secondParent = ParentBusiness::create(['name' => 'Second', 'slug' => 'second']);
    $plan = createOwnershipTestPlan($firstParent, 'price');
    $firstLevel = ParentResellerLevel::create([
        'parent_business_id' => $firstParent->id, 'name' => 'Basic', 'position' => 1,
    ]);
    $secondLevel = ParentResellerLevel::create([
        'parent_business_id' => $secondParent->id, 'name' => 'Basic', 'position' => 1,
    ]);

    ProductPlanParentPrice::create([
        'parent_business_id' => $firstParent->id,
        'product_plan_id' => $plan->id,
        'parent_reseller_level_id' => $firstLevel->id,
        'selling_price' => 150,
    ]);

    expect(fn () => ProductPlanParentPrice::create([
        'parent_business_id' => $firstParent->id,
        'product_plan_id' => $plan->id,
        'parent_reseller_level_id' => $firstLevel->id,
        'selling_price' => 160,
    ]))->toThrow(QueryException::class)
        ->and(fn () => ProductPlanParentPrice::create([
            'parent_business_id' => $firstParent->id,
            'product_plan_id' => $plan->id,
            'parent_reseller_level_id' => $secondLevel->id,
            'selling_price' => 170,
        ]))->toThrow(QueryException::class);
});

it('enforces route priority per plan while provider plan ids remain connection scoped', function () {
    $firstParent = ParentBusiness::create(['name' => 'First', 'slug' => 'first']);
    $secondParent = ParentBusiness::create(['name' => 'Second', 'slug' => 'second']);
    $provider = ProviderConnection::create(['name' => 'Provider', 'slug' => 'provider', 'adapter' => 'provider']);
    $firstConnection = ParentProviderConnection::create([
        'parent_business_id' => $firstParent->id, 'provider_connection_id' => $provider->id, 'name' => 'Primary',
    ]);
    $secondConnection = ParentProviderConnection::create([
        'parent_business_id' => $secondParent->id, 'provider_connection_id' => $provider->id, 'name' => 'Primary',
    ]);
    $firstPlan = createOwnershipTestPlan($firstParent, 'route-one');
    $secondPlan = createOwnershipTestPlan($secondParent, 'route-two');

    ProductPlanProviderRoute::create([
        'parent_business_id' => $firstParent->id, 'product_plan_id' => $firstPlan->id,
        'parent_provider_connection_id' => $firstConnection->id, 'provider_plan_id' => 'shared-id', 'priority' => 1,
    ]);
    ProductPlanProviderRoute::create([
        'parent_business_id' => $secondParent->id, 'product_plan_id' => $secondPlan->id,
        'parent_provider_connection_id' => $secondConnection->id, 'provider_plan_id' => 'shared-id', 'priority' => 1,
    ]);

    expect(fn () => ProductPlanProviderRoute::create([
        'parent_business_id' => $firstParent->id, 'product_plan_id' => $firstPlan->id,
        'parent_provider_connection_id' => $firstConnection->id, 'provider_plan_id' => 'another', 'priority' => 1,
    ]))->toThrow(QueryException::class)
        ->and(fn () => ProductPlanProviderRoute::create([
            'parent_business_id' => $firstParent->id, 'product_plan_id' => $firstPlan->id,
            'parent_provider_connection_id' => $secondConnection->id, 'provider_plan_id' => 'wrong-parent', 'priority' => 2,
        ]))->toThrow(QueryException::class);
});

it('retains transaction routing and monetary snapshots with model casts', function () {
    $transaction = new Transaction;
    $transaction->forceFill([
        'provider_cost_snapshot' => 10,
        'parent_cost_snapshot' => 11,
        'affiliate_cost_snapshot' => 12,
        'customer_price_snapshot' => 13,
        'parent_profit_snapshot' => 1,
        'affiliate_profit_snapshot' => 1,
    ]);

    expect($transaction->provider_cost_snapshot)->toBe('10.00')
        ->and($transaction->parent_cost_snapshot)->toBe('11.00')
        ->and($transaction->affiliate_cost_snapshot)->toBe('12.00')
        ->and($transaction->customer_price_snapshot)->toBe('13.00')
        ->and($transaction->parent_profit_snapshot)->toBe('1.00')
        ->and($transaction->affiliate_profit_snapshot)->toBe('1.00');
});

it('casts normalized values and exposes plan relationships', function () {
    $plan = new ProductPlan;
    $price = new ProductPlanParentPrice(['selling_price' => 20, 'max_profit' => 3]);
    $route = new ProductPlanProviderRoute(['priority' => '2', 'active' => 1]);
    $audit = new MultiParentMigrationAudit(['metadata' => ['source' => 'legacy']]);

    expect($plan->parentPrices()->getRelated())->toBeInstanceOf(ProductPlanParentPrice::class)
        ->and($plan->providerRoutes()->getRelated())->toBeInstanceOf(ProductPlanProviderRoute::class)
        ->and($price->selling_price)->toBe('20.00')
        ->and($price->max_profit)->toBe('3.00')
        ->and($route->priority)->toBe(2)
        ->and($route->active)->toBeTrue()
        ->and($audit->metadata)->toBe(['source' => 'legacy']);
});
