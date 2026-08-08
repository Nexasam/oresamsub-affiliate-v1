<?php

use App\Models\Admin;
use App\Models\Affiliate;
use App\Models\AffiliateUserPlan;
use App\Models\ParentBusiness;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPlan;
use App\Services\AffiliateCatalogGenerationService;
use App\Services\MultiParent\OresamsubFoundationBackfillService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function legacyLevelsAffiliate(ParentBusiness $parent, string $suffix): Affiliate
{
    $resellerLevel = DB::table('parent_reseller_levels')->where('parent_business_id', $parent->id)->first();
    if (! $resellerLevel) {
        $resellerLevelId = DB::table('parent_reseller_levels')->insertGetId([
            'parent_business_id' => $parent->id, 'name' => 'Basic', 'position' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    } else {
        $resellerLevelId = $resellerLevel->id;
    }

    return Affiliate::create([
        'name' => "Legacy {$suffix}", 'slug' => "legacy-{$suffix}", 'affiliate_plan_id' => 1,
        'ip_address' => '127.0.2.'.(Affiliate::count() + 1), 'contact_phone' => "08030000{$suffix}",
        'contact_email' => "legacy-{$suffix}@example.com", 'parent_key' => "key-{$suffix}",
        'parent_email' => "parent-{$suffix}@example.com", 'parent_business_id' => $parent->id,
        'parent_reseller_level_id' => $resellerLevelId,
    ]);
}

it('moves legacy customers to their affiliates level six and hides legacy plans idempotently', function () {
    $parent = ParentBusiness::create(['name' => 'Parent', 'slug' => 'parent']);
    $affiliate = legacyLevelsAffiliate($parent, 'one');
    $otherAffiliate = legacyLevelsAffiliate($parent, 'two');
    $role = Role::create(['role_name' => 'User']);

    $plans = collect(range(1, 12))->mapWithKeys(fn (int $level) => [$level => AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'user_plan_name' => "Level {$level}",
        'plan_level' => $level, 'visibility' => 1,
    ]),
    ]);
    $otherSix = AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $otherAffiliate->id, 'user_plan_name' => 'Other Six',
        'plan_level' => 6, 'visibility' => 1,
    ]);

    $users = collect([6, 7, 12])->mapWithKeys(fn (int $level) => [$level => User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'username' => "legacy-{$level}",
        'first_name' => 'Legacy', 'last_name' => "Level {$level}",
        'email' => "legacy-{$level}@example.com", 'password' => 'password123',
        'role_id' => $role->id, 'user_plan_id' => $plans[$level]->id,
    ]),
    ]);
    $otherUser = User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $otherAffiliate->id, 'username' => 'other-six', 'first_name' => 'Other',
        'last_name' => 'Six', 'email' => 'other-six@example.com', 'password' => 'password123',
        'role_id' => $role->id, 'user_plan_id' => $otherSix->id,
    ]);

    $service = app(OresamsubFoundationBackfillService::class);
    expect($service->consolidateLegacyCustomerLevels($parent, 'batch-one'))->toBe(2)
        ->and($users[6]->fresh()->user_plan_id)->toBe($plans[6]->id)
        ->and($users[7]->fresh()->user_plan_id)->toBe($plans[6]->id)
        ->and($users[12]->fresh()->user_plan_id)->toBe($plans[6]->id)
        ->and($otherUser->fresh()->user_plan_id)->toBe($otherSix->id);

    foreach (range(7, 12) as $level) {
        expect((int) $plans[$level]->fresh()->visibility)->toBe(0);
    }

    $audits = DB::table('multi_parent_migration_audits')
        ->where('action', 'customer_plan_consolidated_to_level_6')->orderBy('entity_id')->get();
    expect($audits)->toHaveCount(2)
        ->and(json_decode($audits[0]->from_value, true))->toBe(['user_plan_id' => $plans[7]->id])
        ->and(json_decode($audits[0]->to_value, true))->toBe(['user_plan_id' => $plans[6]->id]);

    expect($service->consolidateLegacyCustomerLevels($parent, 'batch-two'))->toBe(0)
        ->and(DB::table('multi_parent_migration_audits')->where('action', 'customer_plan_consolidated_to_level_6')->count())->toBe(2)
        ->and(AffiliateUserPlan::withoutGlobalScope('affiliate')->where('affiliate_id', $affiliate->id)->count())->toBe(12);
});

it('generates only missing customer plan positions through level six', function () {
    $parent = ParentBusiness::create(['name' => 'Parent', 'slug' => 'parent']);
    $affiliate = legacyLevelsAffiliate($parent, 'generation');

    foreach (range(1, 6) as $level) {
        UserPlan::create([
            'api_id' => "source-{$level}", 'user_plan_name' => "Source {$level}",
            'plan_level' => $level, 'visibility' => 1,
        ]);
    }
    UserPlan::create([
        'api_id' => 'source-7', 'user_plan_name' => 'Source 7',
        'plan_level' => 7, 'visibility' => 1,
    ]);
    foreach ([1, 2, 4] as $level) {
        AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
            'affiliate_id' => $affiliate->id, 'user_plan_name' => "Existing {$level}",
            'plan_level' => $level,
        ]);
    }

    $result = app(AffiliateCatalogGenerationService::class)->generateUserPlans($affiliate);

    expect($result)->toBe(['created' => 3, 'existing' => 3])
        ->and(AffiliateUserPlan::withoutGlobalScope('affiliate')->where('affiliate_id', $affiliate->id)
            ->orderByRaw('CAST(plan_level AS UNSIGNED)')->pluck('plan_level')->map(fn ($level) => (int) $level)->all())
        ->toBe(range(1, 6));
});

it('rejects a seventh customer plan position and duplicate affiliate positions', function () {
    $parent = ParentBusiness::create(['name' => 'Parent', 'slug' => 'parent']);
    $affiliate = legacyLevelsAffiliate($parent, 'limit');
    $plan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'user_plan_name' => 'Level Six', 'plan_level' => 6,
    ]);

    $admin = Admin::create([
        'name' => 'Platform Owner', 'email' => 'level-owner@example.com',
        'password' => 'password123', 'active' => true,
    ]);
    $this->actingAs($admin, 'platform_admin')
        ->patchJson("/admin/affiliates/{$affiliate->id}/management-user-plans/{$plan->id}", ['plan_level' => 7])
        ->assertUnprocessable();

    expect(fn () => AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'user_plan_name' => 'Duplicate Six', 'plan_level' => 6,
    ]))->toThrow(QueryException::class);
});

it('moves users from duplicate canonical levels without deleting the duplicate plans', function () {
    $parent = ParentBusiness::create(['name' => 'Parent', 'slug' => 'parent']);
    $affiliate = legacyLevelsAffiliate($parent, 'duplicates');
    $role = Role::create(['role_name' => 'User']);
    $canonical = AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'user_plan_name' => 'Canonical Six', 'plan_level' => 6,
    ]);
    $duplicateId = DB::table('affiliate_user_plans')->insertGetId([
        'affiliate_id' => $affiliate->id, 'user_plan_name' => 'Duplicate Six', 'plan_level' => 6,
        'canonical_plan_level' => null, 'visibility' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $user = User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'username' => 'duplicate-user', 'first_name' => 'Duplicate',
        'last_name' => 'User', 'email' => 'duplicate-user@example.com', 'password' => 'password123',
        'role_id' => $role->id, 'user_plan_id' => $duplicateId,
    ]);

    expect(app(OresamsubFoundationBackfillService::class)->consolidateLegacyCustomerLevels($parent, 'duplicates-one'))->toBe(1)
        ->and($user->fresh()->user_plan_id)->toBe($canonical->id)
        ->and(DB::table('affiliate_user_plans')->where('id', $duplicateId)->exists())->toBeTrue()
        ->and((int) DB::table('affiliate_user_plans')->where('id', $duplicateId)->value('visibility'))->toBe(0)
        ->and(DB::table('multi_parent_migration_audits')->where('deterministic_key', "customer-plan-canonicalization:{$user->id}:{$duplicateId}")->count())->toBe(1);

    expect(app(OresamsubFoundationBackfillService::class)->consolidateLegacyCustomerLevels($parent, 'duplicates-two'))->toBe(0)
        ->and(DB::table('multi_parent_migration_audits')->where('deterministic_key', "customer-plan-canonicalization:{$user->id}:{$duplicateId}")->count())->toBe(1);
});

it('rejects cross-affiliate plan corruption before changing any plan or user', function () {
    $parent = ParentBusiness::create(['name' => 'Parent', 'slug' => 'parent']);
    $first = legacyLevelsAffiliate($parent, 'corrupt-one');
    $second = legacyLevelsAffiliate($parent, 'corrupt-two');
    $role = Role::create(['role_name' => 'User']);
    $firstLegacy = AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $first->id, 'user_plan_name' => 'First Legacy', 'plan_level' => 7,
    ]);
    $secondSix = AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $second->id, 'user_plan_name' => 'Second Six', 'plan_level' => 6,
    ]);
    $user = User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $first->id, 'username' => 'corrupt-user', 'first_name' => 'Corrupt',
        'last_name' => 'User', 'email' => 'corrupt-user@example.com', 'password' => 'password123',
        'role_id' => $role->id, 'user_plan_id' => $secondSix->id,
    ]);

    expect(fn () => app(OresamsubFoundationBackfillService::class)->consolidateLegacyCustomerLevels($parent, 'corrupt'))
        ->toThrow(RuntimeException::class, (string) $user->id)
        ->and($user->fresh()->user_plan_id)->toBe($secondSix->id)
        ->and((int) $firstLegacy->fresh()->visibility)->toBe(1)
        ->and(DB::table('multi_parent_migration_audits')->count())->toBe(0);
});

it('keeps preexisting duplicate rows and assignments through migration up and down', function () {
    $migration = require database_path('migrations/2026_08_08_100200_enforce_unique_affiliate_user_plan_levels.php');
    $migration->down();

    $parent = ParentBusiness::create(['name' => 'Parent', 'slug' => 'parent']);
    $affiliate = legacyLevelsAffiliate($parent, 'migration');
    $role = Role::create(['role_name' => 'User']);
    $firstId = DB::table('affiliate_user_plans')->insertGetId([
        'affiliate_id' => $affiliate->id, 'user_plan_name' => 'First', 'plan_level' => 6,
        'visibility' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $secondId = DB::table('affiliate_user_plans')->insertGetId([
        'affiliate_id' => $affiliate->id, 'user_plan_name' => 'Second', 'plan_level' => 6,
        'visibility' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $user = User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'username' => 'migration-user', 'first_name' => 'Migration',
        'last_name' => 'User', 'email' => 'migration-user@example.com', 'password' => 'password123',
        'role_id' => $role->id, 'user_plan_id' => $secondId,
    ]);

    $migration->up();
    expect(DB::table('affiliate_user_plans')->whereIn('id', [$firstId, $secondId])->count())->toBe(2)
        ->and($user->fresh()->user_plan_id)->toBe($secondId)
        ->and((int) DB::table('affiliate_user_plans')->where('id', $firstId)->value('canonical_plan_level'))->toBe(6)
        ->and(DB::table('affiliate_user_plans')->where('id', $secondId)->value('canonical_plan_level'))->toBeNull()
        ->and(Schema::hasColumn('multi_parent_migration_audits', 'deterministic_key'))->toBeTrue();

    $migration->down();
    expect(DB::table('affiliate_user_plans')->whereIn('id', [$firstId, $secondId])->count())->toBe(2)
        ->and($user->fresh()->user_plan_id)->toBe($secondId)
        ->and(Schema::hasColumn('affiliate_user_plans', 'canonical_plan_level'))->toBeFalse()
        ->and(Schema::hasColumn('multi_parent_migration_audits', 'deterministic_key'))->toBeFalse();
});
