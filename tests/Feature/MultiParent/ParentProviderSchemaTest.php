<?php

use App\Models\Affiliate;
use App\Models\AffiliateLicense;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ProductPlan;
use App\Models\ProviderAdapter;
use App\Models\ProviderConnection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function createLegacyAffiliate(array $overrides = []): Affiliate
{
    static $sequence = 0;
    $sequence++;

    return Affiliate::query()->create(array_merge([
        'name' => "Affiliate {$sequence}",
        'slug' => "affiliate-{$sequence}",
        'affiliate_plan_id' => 1,
        'ip_address' => "127.0.0.{$sequence}",
        'contact_phone' => '0800000000'.$sequence,
        'contact_email' => "affiliate{$sequence}@example.test",
        'parent_key' => "legacy-key-{$sequence}",
        'parent_email' => "parent{$sequence}@example.test",
    ], $overrides));
}

it('creates the parent provider and licence schema', function () {
    expect(Schema::hasTable('parent_businesses'))->toBeTrue()
        ->and(Schema::hasTable('parent_admins'))->toBeTrue()
        ->and(Schema::hasTable('provider_adapters'))->toBeTrue()
        ->and(Schema::hasTable('provider_connections'))->toBeTrue()
        ->and(Schema::hasTable('affiliate_licenses'))->toBeTrue()
        ->and(Schema::hasColumns('affiliates', ['parent_business_id', 'provider_connection_id']))->toBeTrue()
        ->and(Schema::hasColumns('product_plans', [
            'parent_business_id',
            'provider_connection_id',
            'upstream_code',
            'provider_cost',
            'status',
            'provider_settings',
            'raw_metadata',
            'last_synced_at',
        ]))->toBeTrue();
});

it('links parent admins connections affiliates and licences to one parent', function () {
    $parent = ParentBusiness::query()->create([
        'name' => 'ABC VTU',
        'slug' => 'abc-vtu',
        'status' => 'active',
    ]);
    $adapter = ProviderAdapter::query()->create([
        'key' => 'abc-vtu',
        'name' => 'ABC VTU',
        'driver' => 'abc_vtu',
        'capabilities' => ['data'],
        'is_active' => true,
    ]);
    $connection = ProviderConnection::query()->create([
        'parent_business_id' => $parent->id,
        'provider_adapter_id' => $adapter->id,
        'name' => 'Production',
        'base_url' => 'https://abc-vtu.example/api',
        'credentials' => ['api_key' => 'secret-key'],
        'settings' => ['timeout_seconds' => 20],
        'status' => 'active',
    ]);
    $parentAdmin = ParentAdmin::query()->create([
        'parent_business_id' => $parent->id,
        'name' => 'ABC Owner',
        'email' => 'owner@abc-vtu.example',
        'password' => 'password',
    ]);
    $affiliate = createLegacyAffiliate([
        'parent_business_id' => $parent->id,
        'provider_connection_id' => $connection->id,
    ]);
    AffiliateLicense::query()->create([
        'affiliate_id' => $affiliate->id,
        'parent_business_id' => $parent->id,
        'status' => 'active',
        'purchase_amount' => 35000,
    ]);

    expect($parentAdmin->parentBusiness->is($parent))->toBeTrue()
        ->and($parent->admins)->toHaveCount(1)
        ->and($parent->providerConnections)->toHaveCount(1)
        ->and($parent->affiliates)->toHaveCount(1)
        ->and($affiliate->parentBusiness->is($parent))->toBeTrue()
        ->and($affiliate->providerConnection->is($connection))->toBeTrue()
        ->and($affiliate->license->parentBusiness->is($parent))->toBeTrue();
});

it('encrypts provider credentials at rest', function () {
    $parent = ParentBusiness::query()->create(['name' => 'Secure Parent', 'slug' => 'secure-parent']);
    $adapter = ProviderAdapter::query()->create([
        'key' => 'secure',
        'name' => 'Secure',
        'driver' => 'secure',
    ]);
    $connection = ProviderConnection::query()->create([
        'parent_business_id' => $parent->id,
        'provider_adapter_id' => $adapter->id,
        'name' => 'Production',
        'credentials' => ['api_key' => 'secret-key'],
    ]);

    $stored = DB::table('provider_connections')->where('id', $connection->id)->value('credentials');

    expect($stored)->not->toContain('secret-key')
        ->and($connection->fresh()->credentials)->toBe(['api_key' => 'secret-key']);
});

it('allows the same upstream plan code for different connections but not twice for one connection', function () {
    $parent = ParentBusiness::query()->create(['name' => 'Plan Parent', 'slug' => 'plan-parent']);
    $adapter = ProviderAdapter::query()->create(['key' => 'plans', 'name' => 'Plans', 'driver' => 'plans']);
    $first = ProviderConnection::query()->create([
        'parent_business_id' => $parent->id,
        'provider_adapter_id' => $adapter->id,
        'name' => 'First',
    ]);
    $second = ProviderConnection::query()->create([
        'parent_business_id' => $parent->id,
        'provider_adapter_id' => $adapter->id,
        'name' => 'Second',
    ]);

    $categoryId = DB::table('product_plan_categories')->insertGetId([
        'api_id' => 'global-mtn-sme',
        'product_plan_category_name' => 'MTN SME',
        'product_id' => DB::table('products')->insertGetId([
            'product_name' => 'Data',
            'slug' => 'data-schema-test',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $attributes = [
        'product_plan_name' => 'MTN 1GB',
        'product_plan_category_id' => $categoryId,
        'upstream_code' => 'MTN-1GB',
        'parent_business_id' => $parent->id,
    ];

    ProductPlan::query()->create($attributes + ['provider_connection_id' => $first->id]);
    ProductPlan::query()->create($attributes + ['provider_connection_id' => $second->id]);

    expect(ProductPlan::query()->where('upstream_code', 'MTN-1GB')->count())->toBe(2);

    ProductPlan::query()->create($attributes + ['provider_connection_id' => $first->id]);
})->throws(QueryException::class);
