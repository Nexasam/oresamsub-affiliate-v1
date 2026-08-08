<?php

use App\Models\ParentBusiness;
use App\Models\ParentResellerLevel;
use App\Services\MultiParent\OresamsubFoundationBackfillService;
use Database\Seeders\OresamsubParentSeeder;
use Illuminate\Support\Facades\DB;

function foundationFixture(string $suffix = 'legacy'): array
{
    $affiliateId = DB::table('affiliates')->insertGetId([
        'name' => "Affiliate {$suffix}", 'slug' => "affiliate-{$suffix}", 'affiliate_plan_id' => 1,
        'ip_address' => '127.0.1.'.(DB::table('affiliates')->count() + 1), 'contact_phone' => "080{$suffix}",
        'contact_email' => "{$suffix}@example.test", 'parent_key' => "key-{$suffix}",
        'parent_email' => "parent-{$suffix}@example.test", 'created_at' => now(), 'updated_at' => now(),
    ]);
    $productId = DB::table('products')->insertGetId([
        'api_id' => "product-{$suffix}", 'slug' => "product-{$suffix}", 'product_name' => "Product {$suffix}",
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $categoryId = DB::table('product_plan_categories')->insertGetId([
        'api_id' => "category-{$suffix}", 'product_plan_category_name' => "Category {$suffix}",
        'product_id' => $productId, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $planId = DB::table('product_plans')->insertGetId(array_merge([
        'api_id' => "api-{$suffix}", 'automation_product_plan_id' => "automation-{$suffix}",
        'product_plan_name' => "Plan {$suffix}", 'product_plan_category_id' => $categoryId,
        'cost_price' => '90', 'admin_cost_price' => '80', 'created_at' => now(), 'updated_at' => now(),
    ], collect(range(1, 6))->mapWithKeys(fn ($level) => ["cost_price_{$level}" => (string) (90 + $level)])->all()));
    $affiliatePlanId = DB::table('affiliate_product_plans')->insertGetId([
        'affiliate_id' => $affiliateId, 'product_plan_name' => "Affiliate plan {$suffix}",
        'product_plan_id' => $planId, 'user_level_1_profit' => '5', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $userId = DB::table('users')->insertGetId([
        'username' => "user-{$suffix}", 'affiliate_id' => $affiliateId, 'first_name' => 'Test', 'last_name' => 'User',
        'role_id' => '1', 'email' => "user-{$suffix}@example.test", 'password' => 'secret',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $transactionId = DB::table('transactions')->insertGetId([
        'affiliate_id' => $affiliateId, 'api_id' => "txn-{$suffix}", 'txn_reference' => "ref-{$suffix}",
        'affiliate_product_plan_id' => $affiliatePlanId, 'user_id' => $userId, 'wallet_category' => 'main_wallet',
        'amount' => '110', 'balance_before' => '500', 'balance_after' => '390', 'status' => '1',
        'description' => 'Historical transaction', 'created_at' => now(), 'updated_at' => now(),
    ]);

    return compact('affiliateId', 'planId', 'affiliatePlanId', 'transactionId');
}

beforeEach(function () {
    $this->seed(OresamsubParentSeeder::class);
});

it('reports dry-run work while rolling back every write', function () {
    $fixture = foundationFixture('dry');

    $counts = app(OresamsubFoundationBackfillService::class)->run(true);

    expect($counts['affiliates'])->toBe(1)->and($counts['plans'])->toBe(1)
        ->and($counts['prices'])->toBe(6)->and($counts['routes'])->toBe(1)
        ->and($counts['transactions'])->toBe(1)->and($counts['audits'])->toBeGreaterThan(0)
        ->and(DB::table('affiliates')->where('id', $fixture['affiliateId'])->value('parent_business_id'))->toBeNull()
        ->and(DB::table('product_plan_parent_prices')->count())->toBe(0)
        ->and(DB::table('product_plan_provider_routes')->count())->toBe(0)
        ->and(DB::table('multi_parent_migration_audits')->count())->toBe(0)
        ->and(DB::table('transactions')->where('id', $fixture['transactionId'])->value('parent_business_id'))->toBeNull();
});

it('commits once, preserves legacy money fields, and is idempotent', function () {
    $first = foundationFixture('first');
    $second = foundationFixture('second');
    $before = DB::table('transactions')->where('id', $first['transactionId'])->first();

    $counts = app(OresamsubFoundationBackfillService::class)->run(false);
    $parent = ParentBusiness::where('slug', 'oresamsub')->firstOrFail();
    $basic = ParentResellerLevel::where('parent_business_id', $parent->id)->where('position', 1)->firstOrFail();
    $transaction = DB::table('transactions')->where('id', $first['transactionId'])->first();

    expect($counts['affiliates'])->toBe(2)->and($counts['plans'])->toBe(2)
        ->and(DB::table('affiliates')->where('parent_business_id', $parent->id)->where('parent_reseller_level_id', $basic->id)->count())->toBe(2)
        ->and(DB::table('product_plan_parent_prices')->count())->toBe(12)
        ->and(DB::table('product_plan_provider_routes')->where('priority', 1)->count())->toBe(2)
        ->and($transaction->parent_business_id)->toBe($parent->id)
        ->and($transaction->provider_plan_id_snapshot)->toBe('automation-first')
        ->and($transaction->amount)->toBe($before->amount)->and($transaction->balance_before)->toBe($before->balance_before)
        ->and($transaction->balance_after)->toBe($before->balance_after)->and($transaction->status)->toBe($before->status)
        ->and($transaction->txn_reference)->toBe($before->txn_reference);

    $again = app(OresamsubFoundationBackfillService::class)->run(false);
    expect($again['affiliates'])->toBe(0)->and($again['plans'])->toBe(0)->and($again['prices'])->toBe(0)
        ->and($again['routes'])->toBe(0)->and($again['transactions'])->toBe(0)->and($again['audits'])->toBe(0);
});

it('skips fully foreign-owned records and rejects partial ownership before any mutation', function () {
    $foreign = ParentBusiness::create(['name' => 'Foreign', 'slug' => 'foreign']);
    $level = ParentResellerLevel::create(['parent_business_id' => $foreign->id, 'name' => 'Basic', 'position' => 1]);
    $owned = foundationFixture('foreign');
    DB::table('affiliates')->where('id', $owned['affiliateId'])->update(['parent_business_id' => $foreign->id, 'parent_reseller_level_id' => $level->id]);
    DB::table('product_plans')->where('id', $owned['planId'])->update(['parent_business_id' => $foreign->id]);
    app(OresamsubFoundationBackfillService::class)->run(false);
    expect(DB::table('affiliates')->where('id', $owned['affiliateId'])->value('parent_business_id'))->toBe($foreign->id)
        ->and(DB::table('product_plans')->where('id', $owned['planId'])->value('parent_business_id'))->toBe($foreign->id);

    $partial = foundationFixture('partial');
    DB::statement('DROP TRIGGER affiliates_parent_ownership_update');
    DB::table('affiliates')->where('id', $partial['affiliateId'])->update(['parent_business_id' => $foreign->id]);
    $beforeAuditCount = DB::table('multi_parent_migration_audits')->count();

    expect(fn () => app(OresamsubFoundationBackfillService::class)->run(false))
        ->toThrow(RuntimeException::class, (string) $partial['affiliateId'])
        ->and(DB::table('product_plans')->where('id', $partial['planId'])->value('parent_business_id'))->toBeNull()
        ->and(DB::table('multi_parent_migration_audits')->count())->toBe($beforeAuditCount);
});

it('requires exactly one command mode', function () {
    $this->artisan('multi-parent:backfill-oresamsub-foundation')->assertFailed();
    $this->artisan('multi-parent:backfill-oresamsub-foundation --dry-run --commit')->assertFailed();
});
