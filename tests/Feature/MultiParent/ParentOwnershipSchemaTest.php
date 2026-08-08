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

function ownershipTestAffiliateAttributes(string $suffix): array
{
    return [
        'name' => "Affiliate {$suffix}",
        'slug' => "affiliate-{$suffix}",
        'ip_address' => "127.0.0.{$suffix}",
        'contact_phone' => "080000000{$suffix}",
        'contact_email' => "affiliate-{$suffix}@example.test",
        'parent_key' => "parent-key-{$suffix}",
        'parent_email' => "parent-{$suffix}@example.test",
        'created_at' => now(),
        'updated_at' => now(),
    ];
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

it('requires affiliate parent ownership to be both null or a matching pair on insert and update', function () {
    $firstParent = ParentBusiness::create(['name' => 'First', 'slug' => 'first']);
    $secondParent = ParentBusiness::create(['name' => 'Second', 'slug' => 'second']);
    $firstLevel = ParentResellerLevel::create([
        'parent_business_id' => $firstParent->id, 'name' => 'Basic', 'position' => 1,
    ]);
    $secondLevel = ParentResellerLevel::create([
        'parent_business_id' => $secondParent->id, 'name' => 'Basic', 'position' => 1,
    ]);

    $unownedAffiliateId = DB::table('affiliates')->insertGetId(ownershipTestAffiliateAttributes('1'));
    DB::table('affiliates')->insert([
        ...ownershipTestAffiliateAttributes('2'),
        'parent_business_id' => $firstParent->id,
        'parent_reseller_level_id' => $firstLevel->id,
    ]);

    expect(fn () => DB::table('affiliates')->insert([
        ...ownershipTestAffiliateAttributes('3'),
        'parent_business_id' => $firstParent->id,
    ]))->toThrow(QueryException::class)
        ->and(fn () => DB::table('affiliates')->insert([
            ...ownershipTestAffiliateAttributes('4'),
            'parent_reseller_level_id' => $firstLevel->id,
        ]))->toThrow(QueryException::class)
        ->and(fn () => DB::table('affiliates')->insert([
            ...ownershipTestAffiliateAttributes('5'),
            'parent_business_id' => $firstParent->id,
            'parent_reseller_level_id' => $secondLevel->id,
        ]))->toThrow(QueryException::class)
        ->and(fn () => DB::table('affiliates')->where('id', $unownedAffiliateId)->update([
            'parent_business_id' => $firstParent->id,
        ]))->toThrow(QueryException::class)
        ->and(fn () => DB::table('affiliates')->where('id', $unownedAffiliateId)->update([
            'parent_reseller_level_id' => $firstLevel->id,
        ]))->toThrow(QueryException::class)
        ->and(fn () => DB::table('affiliates')->where('id', $unownedAffiliateId)->update([
            'parent_business_id' => $firstParent->id,
            'parent_reseller_level_id' => $secondLevel->id,
        ]))->toThrow(QueryException::class)
        ->and(DB::table('affiliates')->where('id', $unownedAffiliateId)->update([
            'parent_business_id' => $firstParent->id,
            'parent_reseller_level_id' => $firstLevel->id,
        ]))->toBe(1)
        ->and(DB::table('affiliates')->where('id', $unownedAffiliateId)->update([
            'parent_business_id' => null,
            'parent_reseller_level_id' => null,
        ]))->toBe(1);
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
    $parent = ParentBusiness::create(['name' => 'Parent', 'slug' => 'parent']);
    $provider = ProviderConnection::create(['name' => 'Provider', 'slug' => 'provider', 'adapter' => 'provider']);
    $connection = ParentProviderConnection::create([
        'parent_business_id' => $parent->id, 'provider_connection_id' => $provider->id, 'name' => 'Primary',
    ]);
    $plan = createOwnershipTestPlan($parent, 'transaction');
    $route = ProductPlanProviderRoute::create([
        'parent_business_id' => $parent->id, 'product_plan_id' => $plan->id,
        'parent_provider_connection_id' => $connection->id, 'provider_plan_id' => 'provider-plan', 'priority' => 1,
    ]);
    $affiliateId = DB::table('affiliates')->insertGetId(ownershipTestAffiliateAttributes('6'));
    $userId = DB::table('users')->insertGetId([
        'username' => 'snapshot-user', 'affiliate_id' => $affiliateId, 'first_name' => 'Snapshot',
        'last_name' => 'User', 'role_id' => '1', 'email' => 'snapshot@example.test', 'password' => 'secret',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $affiliateProductPlanId = DB::table('affiliate_product_plans')->insertGetId([
        'affiliate_id' => $affiliateId, 'product_plan_name' => 'Snapshot plan', 'product_plan_id' => $plan->id,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $transaction = Transaction::create([
        'affiliate_id' => $affiliateId,
        'api_id' => 'snapshot-transaction',
        'affiliate_product_plan_id' => $affiliateProductPlanId,
        'user_id' => $userId,
        'wallet_category' => 'main_wallet',
        'amount' => '13',
        'balance_before' => '20',
        'balance_after' => '7',
        'description' => 'Snapshot retention test',
        'parent_business_id' => $parent->id,
        'parent_provider_connection_id' => $connection->id,
        'product_plan_provider_route_id' => $route->id,
        'provider_plan_id_snapshot' => 'provider-plan',
        'provider_reference' => 'provider-reference',
        'routing_status' => 'submitted',
        'provider_cost_snapshot' => 10,
        'parent_cost_snapshot' => 11,
        'affiliate_cost_snapshot' => 12,
        'customer_price_snapshot' => 13,
        'parent_profit_snapshot' => 1,
        'affiliate_profit_snapshot' => 1,
    ]);
    $transaction = $transaction->fresh();

    expect($transaction->parent_business_id)->toBe($parent->id)
        ->and($transaction->parent_provider_connection_id)->toBe($connection->id)
        ->and($transaction->product_plan_provider_route_id)->toBe($route->id)
        ->and($transaction->provider_plan_id_snapshot)->toBe('provider-plan')
        ->and($transaction->provider_reference)->toBe('provider-reference')
        ->and($transaction->routing_status)->toBe('submitted')
        ->and($transaction->provider_cost_snapshot)->toBe('10.00')
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
