<?php

use App\Models\Affiliate;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ProductPlan;
use App\Models\ProviderAdapter;
use App\Models\ProviderConnection;
use App\Models\Role;
use App\Models\User;
use App\Services\MultiParent\OresamsubBackfillService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

function seedOresamsubLegacyData(): array
{
    $productId = DB::table('products')->insertGetId([
        'api_id' => 'data-backfill-api',
        'product_name' => 'Data',
        'slug' => 'data-backfill-test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $categoryId = DB::table('product_plan_categories')->insertGetId([
        'api_id' => 'mtn-sme-backfill',
        'product_plan_category_name' => 'MTN SME',
        'product_id' => $productId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $plan = ProductPlan::query()->create([
        'api_id' => 'legacy-plan-100',
        'automation_product_plan_id' => 'ORE-MTN-1GB',
        'product_plan_name' => 'MTN 1GB',
        'product_plan_category_id' => $categoryId,
        'cost_price' => '600',
    ]);

    $sourceAffiliate = Affiliate::query()->create([
        'name' => 'OresamSub',
        'slug' => 'oresamsub',
        'affiliate_plan_id' => 1,
        'ip_address' => '127.0.1.1',
        'domain_url' => 'oresamsub.com',
        'contact_phone' => '08010000001',
        'contact_email' => 'hello@oresamsub.test',
        'parent_key' => 'oresamsub-parent-key',
        'parent_email' => 'parent@oresamsub.test',
    ]);
    $otherAffiliate = Affiliate::query()->create([
        'name' => 'Existing Affiliate',
        'slug' => 'existing-affiliate',
        'affiliate_plan_id' => 1,
        'ip_address' => '127.0.1.2',
        'domain_url' => 'affiliate.example',
        'contact_phone' => '08010000002',
        'contact_email' => 'affiliate@example.test',
        'parent_key' => 'affiliate-parent-key',
        'parent_email' => 'affiliate-parent@example.test',
    ]);
    $adminRole = Role::query()->create(['role_name' => 'Admin']);

    $sourceAdmin = User::query()->create([
        'username' => 'oresam-admin',
        'affiliate_id' => $sourceAffiliate->id,
        'first_name' => 'Oresam',
        'last_name' => 'Owner',
        'phone_number' => '08020000001',
        'role_id' => $adminRole->id,
        'email' => 'owner@oresamsub.test',
        'password' => 'secret-password',
    ]);
    $otherAdmin = User::query()->create([
        'username' => 'affiliate-admin',
        'affiliate_id' => $otherAffiliate->id,
        'first_name' => 'Affiliate',
        'last_name' => 'Admin',
        'phone_number' => '08020000002',
        'role_id' => $adminRole->id,
        'email' => 'admin@affiliate.test',
        'password' => 'secret-password',
    ]);

    return compact('plan', 'sourceAffiliate', 'otherAffiliate', 'sourceAdmin', 'otherAdmin');
}

it('backfills OresamSub ownership licences and plans without changing legacy ids', function () {
    $legacy = seedOresamsubLegacyData();

    $first = app(OresamsubBackfillService::class)->run(
        sourceAffiliateId: $legacy['sourceAffiliate']->id,
        migrateAdmins: true,
    );
    $second = app(OresamsubBackfillService::class)->run(
        sourceAffiliateId: $legacy['sourceAffiliate']->id,
        migrateAdmins: true,
    );

    $parent = ParentBusiness::query()->where('slug', 'oresamsub')->firstOrFail();
    $connection = ProviderConnection::query()->where('parent_business_id', $parent->id)->firstOrFail();

    expect($first['affiliates_updated'])->toBe(2)
        ->and($second['affiliates_updated'])->toBe(0)
        ->and(ParentBusiness::query()->where('slug', 'oresamsub')->count())->toBe(1)
        ->and(ProviderConnection::query()->where('parent_business_id', $parent->id)->count())->toBe(1)
        ->and(DB::table('affiliate_licenses')->count())->toBe(2)
        ->and(Affiliate::query()->whereNull('parent_business_id')->count())->toBe(0)
        ->and($legacy['plan']->fresh()->id)->toBe($legacy['plan']->id)
        ->and($legacy['plan']->fresh()->parent_business_id)->toBe($parent->id)
        ->and($legacy['plan']->fresh()->provider_connection_id)->toBe($connection->id)
        ->and($legacy['plan']->fresh()->upstream_code)->toBe('ORE-MTN-1GB')
        ->and($legacy['plan']->fresh()->provider_cost)->toBe('600.00');
});

it('copies only admin users from the selected OresamSub source affiliate', function () {
    $legacy = seedOresamsubLegacyData();
    $sourcePasswordHash = DB::table('users')->where('id', $legacy['sourceAdmin']->id)->value('password');

    app(OresamsubBackfillService::class)->run(
        sourceAffiliateId: $legacy['sourceAffiliate']->id,
        migrateAdmins: true,
    );

    expect(ParentAdmin::query()->pluck('email')->all())->toBe([$legacy['sourceAdmin']->email])
        ->and(DB::table('parent_admins')->value('password'))->toBe($sourcePasswordHash)
        ->and(User::query()->find($legacy['sourceAdmin']->id))->not->toBeNull()
        ->and(ParentAdmin::query()->where('email', $legacy['otherAdmin']->email)->exists())->toBeFalse();
});

it('reports a dry run without writing any multi parent records', function () {
    $legacy = seedOresamsubLegacyData();

    $exitCode = Artisan::call('multi-parent:backfill-oresamsub', [
        '--source-affiliate' => $legacy['sourceAffiliate']->id,
        '--migrate-admins' => true,
        '--dry-run' => true,
    ]);

    expect($exitCode)->toBe(0)
        ->and(ParentBusiness::query()->count())->toBe(0)
        ->and(ParentAdmin::query()->count())->toBe(0)
        ->and(ProviderConnection::query()->count())->toBe(0)
        ->and(DB::table('affiliate_licenses')->count())->toBe(0)
        ->and($legacy['sourceAffiliate']->fresh()->parent_business_id)->toBeNull()
        ->and($legacy['plan']->fresh()->provider_connection_id)->toBeNull()
        ->and(Artisan::output())->toContain('DRY RUN');
});

it('requires an explicit source affiliate when migrating admins', function () {
    seedOresamsubLegacyData();

    $exitCode = Artisan::call('multi-parent:backfill-oresamsub', [
        '--migrate-admins' => true,
        '--dry-run' => true,
    ]);

    expect($exitCode)->toBe(1)
        ->and(Artisan::output())->toContain('--source-affiliate is required');
});

it('does not reclaim records already owned by another parent', function () {
    $legacy = seedOresamsubLegacyData();
    app(OresamsubBackfillService::class)->run();

    $otherParent = ParentBusiness::query()->create(['name' => 'Other Parent', 'slug' => 'other-parent']);
    $otherAdapter = ProviderAdapter::query()->create([
        'key' => 'other-provider',
        'name' => 'Other Provider',
        'driver' => 'generic',
    ]);
    $otherConnection = ProviderConnection::query()->create([
        'parent_business_id' => $otherParent->id,
        'provider_adapter_id' => $otherAdapter->id,
        'name' => 'Production',
    ]);

    $legacy['otherAffiliate']->update([
        'parent_business_id' => $otherParent->id,
        'provider_connection_id' => $otherConnection->id,
    ]);
    DB::table('affiliate_licenses')->where('affiliate_id', $legacy['otherAffiliate']->id)->update([
        'parent_business_id' => $otherParent->id,
    ]);
    $legacy['plan']->update([
        'parent_business_id' => $otherParent->id,
        'provider_connection_id' => $otherConnection->id,
        'upstream_code' => 'OTHER-PLAN',
    ]);

    app(OresamsubBackfillService::class)->run();

    expect($legacy['otherAffiliate']->fresh()->parent_business_id)->toBe($otherParent->id)
        ->and($legacy['otherAffiliate']->fresh()->provider_connection_id)->toBe($otherConnection->id)
        ->and($legacy['plan']->fresh()->parent_business_id)->toBe($otherParent->id)
        ->and($legacy['plan']->fresh()->provider_connection_id)->toBe($otherConnection->id)
        ->and($legacy['plan']->fresh()->upstream_code)->toBe('OTHER-PLAN');
});
