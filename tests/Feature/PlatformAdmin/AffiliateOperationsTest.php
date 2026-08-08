<?php

use App\Models\Admin;
use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\AffiliateProductPlanCategory;
use App\Models\AffiliateUserPlan;
use App\Models\ParentBusiness;
use App\Models\ParentResellerLevel;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function platformOperationsAffiliate(array $overrides = []): Affiliate
{
    static $sequence = 0;
    $sequence++;

    return Affiliate::create(array_merge([
        'name' => 'Affiliate '.$sequence,
        'slug' => 'affiliate-'.$sequence,
        'affiliate_plan_id' => 1,
        'ip_address' => '127.0.1.'.$sequence,
        'contact_phone' => '0802000000'.$sequence,
        'contact_email' => "affiliate{$sequence}@example.com",
        'parent_key' => 'key-'.$sequence,
        'parent_email' => "parent{$sequence}@example.com",
    ], $overrides));
}

function platformOperationsAdmin(): Admin
{
    return Admin::create([
        'name' => 'Platform Owner',
        'email' => 'platform-operations@example.com',
        'password' => 'password123',
        'active' => true,
    ]);
}

it('stores affiliate-specific margin defaults without changing existing plans by default', function () {
    $admin = platformOperationsAdmin();
    $affiliate = platformOperationsAffiliate();
    $product = Product::create(['api_id' => 'product-data-one', 'product_name' => 'Data', 'slug' => 'data']);
    $category = ProductPlanCategory::create([
        'api_id' => 'category-data-one',
        'product_id' => $product->id,
        'product_plan_category_name' => 'SME',
    ]);
    $globalPlan = ProductPlan::create([
        'product_plan_name' => '1GB',
        'product_plan_category_id' => $category->id,
        'profit_category' => 'flat',
    ]);
    $affiliatePlan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id,
        'product_plan_id' => $globalPlan->id,
        'product_plan_name' => 'Custom 1GB',
        'user_level_1_profit' => 75,
    ]);

    $this->actingAs($admin, 'platform_admin')
        ->patchJson("/admin/affiliates/{$affiliate->id}/margin-defaults", [
            'default_flat_profit_margin' => 65,
            'default_percent_profit_margin' => 1.5,
            'apply_to_existing' => false,
        ])
        ->assertOk()
        ->assertJsonPath('updated_plans', 0);

    expect((float) $affiliate->fresh()->default_flat_profit_margin)->toBe(65.0)
        ->and((float) $affiliate->fresh()->default_percent_profit_margin)->toBe(1.5)
        ->and((float) $affiliatePlan->fresh()->user_level_1_profit)->toBe(75.0);
});

it('can deliberately apply new defaults to all existing levels', function () {
    $admin = platformOperationsAdmin();
    $affiliate = platformOperationsAffiliate();
    $product = Product::create(['api_id' => 'product-airtime-two', 'product_name' => 'Airtime', 'slug' => 'airtime']);
    $category = ProductPlanCategory::create([
        'api_id' => 'category-airtime-two',
        'product_id' => $product->id,
        'product_plan_category_name' => 'Airtime',
    ]);
    $globalPlan = ProductPlan::create([
        'product_plan_name' => 'Airtime',
        'product_plan_category_id' => $category->id,
        'profit_category' => 'percent',
    ]);
    $affiliatePlan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id,
        'product_plan_id' => $globalPlan->id,
        'product_plan_name' => 'Airtime',
        'user_level_1_profit' => 4,
    ]);

    $this->actingAs($admin, 'platform_admin')
        ->patchJson("/admin/affiliates/{$affiliate->id}/margin-defaults", [
            'default_flat_profit_margin' => 50,
            'default_percent_profit_margin' => 2.25,
            'apply_to_existing' => true,
        ])
        ->assertOk()
        ->assertJsonPath('updated_plans', 1);

    $affiliatePlan->refresh();
    foreach (range(1, 6) as $level) {
        expect((float) $affiliatePlan->{"user_level_{$level}_profit"})->toBe(2.25);
    }
});

it('does not allow a catalog plan to be changed through another affiliate', function () {
    $admin = platformOperationsAdmin();
    $first = platformOperationsAffiliate();
    $second = platformOperationsAffiliate();
    $product = Product::create(['api_id' => 'product-data-three', 'product_name' => 'Data', 'slug' => 'data']);
    $category = ProductPlanCategory::create(['api_id' => 'category-data-three', 'product_id' => $product->id, 'product_plan_category_name' => 'SME']);
    $globalPlan = ProductPlan::create(['product_plan_name' => '1GB', 'product_plan_category_id' => $category->id]);
    $plan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $first->id,
        'product_plan_id' => $globalPlan->id,
        'product_plan_name' => 'Original name',
    ]);

    $this->actingAs($admin, 'platform_admin')
        ->patchJson("/admin/affiliates/{$second->id}/catalog/plans/{$plan->id}", [
            'product_plan_name' => 'Wrong tenant update',
        ])
        ->assertNotFound();

    expect($plan->fresh()->product_plan_name)->toBe('Original name');
});

it('renders the operations, master catalog and reporting workspaces', function () {
    $admin = platformOperationsAdmin();
    $affiliate = platformOperationsAffiliate();

    $this->actingAs($admin, 'platform_admin')
        ->get("/admin/affiliates/{$affiliate->id}/operations")
        ->assertOk()
        ->assertSee('Default product margins');

    $this->actingAs($admin, 'platform_admin')
        ->get('/admin/catalog')
        ->assertOk()
        ->assertSee('Global catalog');

    $this->actingAs($admin, 'platform_admin')
        ->get('/admin/affiliate-catalog')
        ->assertOk()
        ->assertSee('Affiliate product plans', false);

    $this->actingAs($admin, 'platform_admin')
        ->get('/admin/reports')
        ->assertOk()
        ->assertSee('Reports & estimated profit');

    $this->actingAs($admin, 'platform_admin')
        ->get('/admin/affiliate-users')
        ->assertOk()
        ->assertSee('Affiliate users & user plans')
        ->assertSee('Credit wallet');
});

it('assigns only user plans owned by the users affiliate', function () {
    $admin = platformOperationsAdmin();
    $first = platformOperationsAffiliate();
    $second = platformOperationsAffiliate();
    $role = Role::create(['role_name' => 'User']);
    $firstPlan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $first->id, 'user_plan_name' => 'Basic', 'plan_level' => 1,
    ]);
    $secondPlan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $second->id, 'user_plan_name' => 'Gold', 'plan_level' => 2,
    ]);
    $user = User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $first->id, 'username' => 'managed-user', 'first_name' => 'Managed',
        'last_name' => 'User', 'email' => 'managed-user@example.com', 'password' => 'password123',
        'role_id' => $role->id, 'user_plan_id' => $firstPlan->id,
    ]);

    $this->actingAs($admin, 'platform_admin')
        ->patchJson("/admin/affiliates/{$first->id}/management-users/{$user->id}", [
            'user_plan_id' => $secondPlan->id,
        ])
        ->assertUnprocessable();

    expect($user->fresh()->user_plan_id)->toBe($firstPlan->id);
});

it('edits an affiliate user and its affiliate user plan', function () {
    $admin = platformOperationsAdmin();
    $affiliate = platformOperationsAffiliate();
    $role = Role::create(['role_name' => 'User']);
    $plan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'user_plan_name' => 'Basic', 'plan_level' => 1,
    ]);
    $user = User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'username' => 'editable-user', 'first_name' => 'Before',
        'last_name' => 'User', 'email' => 'editable-user@example.com', 'password' => 'password123',
        'role_id' => $role->id,
    ]);

    $this->actingAs($admin, 'platform_admin')
        ->getJson("/admin/affiliates/{$affiliate->id}/management-users")
        ->assertOk()
        ->assertJsonPath('plans.0.id', $plan->id)
        ->assertJsonPath('users.data.0.id', $user->id);

    $this->actingAs($admin, 'platform_admin')
        ->patchJson("/admin/affiliates/{$affiliate->id}/management-users/{$user->id}", [
            'first_name' => 'After',
            'user_plan_id' => $plan->id,
            'pin' => '567890',
        ])
        ->assertOk()
        ->assertJsonPath('user.first_name', 'After')
        ->assertJsonPath('user.user_plan.id', $plan->id);

    $this->actingAs($admin, 'platform_admin')
        ->patchJson("/admin/affiliates/{$affiliate->id}/management-user-plans/{$plan->id}", [
            'updated_user_plan_name' => 'Starter',
            'is_default' => 1,
            'visibility' => 1,
            'max_profit' => 70,
        ])
        ->assertOk();

    expect($plan->fresh()->updated_user_plan_name)->toBe('Starter')
        ->and((float) $plan->fresh()->max_profit)->toBe(70.0)
        ->and($user->fresh()->user_plan_id)->toBe($plan->id)
        ->and($user->fresh()->pin)->toBe('567890');
});

it('lists users across all affiliates and identifies their affiliate', function () {
    $admin = platformOperationsAdmin();
    $first = platformOperationsAffiliate();
    $second = platformOperationsAffiliate();
    $role = Role::create(['role_name' => 'User']);

    foreach ([[$first, 'one'], [$second, 'two']] as [$affiliate, $suffix]) {
        User::withoutGlobalScope('affiliate')->create([
            'affiliate_id' => $affiliate->id, 'username' => "user-{$suffix}",
            'first_name' => 'All', 'last_name' => ucfirst($suffix),
            'email' => "all-{$suffix}@example.com", 'password' => 'password123', 'role_id' => $role->id,
        ]);
    }

    $response = $this->actingAs($admin, 'platform_admin')
        ->getJson('/admin/affiliate-users/data')
        ->assertOk()
        ->json('users.data');

    expect(collect($response)->pluck('affiliate.name')->sort()->values()->all())
        ->toBe(collect([$first->name, $second->name])->sort()->values()->all());
});

it('generates missing affiliate plans and categories without duplicates', function () {
    $admin = platformOperationsAdmin();
    $parent = ParentBusiness::create(['name' => 'Generation Parent', 'slug' => 'generation-parent']);
    $parentLevel = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Basic', 'position' => 1]);
    $affiliate = platformOperationsAffiliate([
        'parent_business_id' => $parent->id,
        'parent_reseller_level_id' => $parentLevel->id,
        'default_flat_profit_margin' => 65,
        'default_percent_profit_margin' => 2,
    ]);
    UserPlan::create([
        'api_id' => 'global-basic-generation',
        'user_plan_name' => 'Basic Generation',
        'plan_level' => 1,
        'is_default' => 1,
        'visibility' => 1,
    ]);
    $product = Product::create([
        'api_id' => 'generation-product',
        'product_name' => 'Generation Data',
        'slug' => 'generation-data',
    ]);
    $category = ProductPlanCategory::create([
        'api_id' => 'generation-category',
        'product_plan_category_name' => 'Generation SME',
        'product_id' => $product->id,
    ]);
    ProductPlan::create([
        'parent_business_id' => $parent->id,
        'product_plan_name' => 'Generation 1GB',
        'product_plan_category_id' => $category->id,
        'profit_category' => 'flat',
    ]);

    foreach ([
        "/admin/affiliates/{$affiliate->id}/management-user-plans/generate",
        "/admin/affiliates/{$affiliate->id}/catalog/categories/generate",
        "/admin/affiliates/{$affiliate->id}/catalog/plans/generate",
    ] as $url) {
        $this->actingAs($admin, 'platform_admin')->postJson($url)->assertOk()->assertJsonPath('created', 1);
        $this->actingAs($admin, 'platform_admin')->postJson($url)->assertOk()->assertJsonPath('created', 0);
    }

    expect(AffiliateUserPlan::withoutGlobalScope('affiliate')->where('affiliate_id', $affiliate->id)->count())->toBe(1)
        ->and(AffiliateProductPlanCategory::withoutGlobalScope('affiliate')->where('affiliate_id', $affiliate->id)->count())->toBe(1)
        ->and(AffiliateProductPlan::withoutGlobalScope('affiliate')->where('affiliate_id', $affiliate->id)->count())->toBe(1)
        ->and((float) AffiliateProductPlan::withoutGlobalScope('affiliate')->where('affiliate_id', $affiliate->id)->value('user_level_1_profit'))->toBe(65.0);
});

it('generates only plans and represented categories belonging to the affiliates parent', function () {
    $admin = platformOperationsAdmin();
    $firstParent = ParentBusiness::create(['name' => 'First Parent', 'slug' => 'catalog-first-parent']);
    $secondParent = ParentBusiness::create(['name' => 'Second Parent', 'slug' => 'catalog-second-parent']);
    $firstLevel = ParentResellerLevel::create(['parent_business_id' => $firstParent->id, 'name' => 'Basic', 'position' => 1]);
    $affiliate = platformOperationsAffiliate([
        'parent_business_id' => $firstParent->id,
        'parent_reseller_level_id' => $firstLevel->id,
    ]);
    $product = Product::create(['api_id' => 'scoped-generation-product', 'product_name' => 'Data', 'slug' => 'scoped-generation-data']);
    $firstCategory = ProductPlanCategory::create(['api_id' => 'scoped-first-category', 'product_id' => $product->id, 'product_plan_category_name' => 'First SME']);
    $secondCategory = ProductPlanCategory::create(['api_id' => 'scoped-second-category', 'product_id' => $product->id, 'product_plan_category_name' => 'Second SME']);
    ProductPlan::create(['parent_business_id' => $firstParent->id, 'product_plan_category_id' => $firstCategory->id, 'product_plan_name' => 'First 1GB']);
    ProductPlan::create(['parent_business_id' => $secondParent->id, 'product_plan_category_id' => $secondCategory->id, 'product_plan_name' => 'Second 1GB']);

    $this->actingAs($admin, 'platform_admin')->postJson("/admin/affiliates/{$affiliate->id}/catalog/categories/generate")
        ->assertOk()->assertJsonPath('created', 1);
    $this->actingAs($admin, 'platform_admin')->postJson("/admin/affiliates/{$affiliate->id}/catalog/plans/generate")
        ->assertOk()->assertJsonPath('created', 1);

    expect(AffiliateProductPlan::withoutGlobalScope('affiliate')->where('affiliate_id', $affiliate->id)->pluck('product_plan_name')->all())
        ->toBe(['First 1GB'])
        ->and(AffiliateProductPlanCategory::withoutGlobalScope('affiliate')->where('affiliate_id', $affiliate->id)->pluck('product_plan_category_name')->all())
        ->toBe(['First SME']);
});

it('rejects attaching another parents product plan to an affiliate', function () {
    $firstParent = ParentBusiness::create(['name' => 'Guarded First Parent', 'slug' => 'guarded-first-parent']);
    $secondParent = ParentBusiness::create(['name' => 'Guarded Second Parent', 'slug' => 'guarded-second-parent']);
    $firstLevel = ParentResellerLevel::create(['parent_business_id' => $firstParent->id, 'name' => 'Basic', 'position' => 1]);
    $affiliate = platformOperationsAffiliate(['parent_business_id' => $firstParent->id, 'parent_reseller_level_id' => $firstLevel->id]);
    $product = Product::create(['api_id' => 'guarded-product', 'product_name' => 'Data', 'slug' => 'guarded-data']);
    $category = ProductPlanCategory::create(['api_id' => 'guarded-category', 'product_id' => $product->id, 'product_plan_category_name' => 'Guarded SME']);
    $foreignPlan = ProductPlan::create(['parent_business_id' => $secondParent->id, 'product_plan_category_id' => $category->id, 'product_plan_name' => 'Foreign 1GB']);

    expect(fn () => AffiliateProductPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id,
        'product_plan_id' => $foreignPlan->id,
        'product_plan_name' => 'Invalid foreign plan',
    ]))->toThrow(InvalidArgumentException::class, 'Affiliate and product plan must belong to the same parent business.');
});

it('persists global catalog visibility and profit controls', function () {
    $admin = platformOperationsAdmin();
    $product = Product::create([
        'api_id' => 'global-persistence-product',
        'product_name' => 'Persistence Data',
        'slug' => 'persistence-data',
    ]);
    $category = ProductPlanCategory::create([
        'api_id' => 'global-persistence-category',
        'product_plan_category_name' => 'Persistence SME',
        'product_id' => $product->id,
    ]);
    $plan = ProductPlan::create([
        'product_plan_name' => 'Persistence 1GB',
        'product_plan_category_id' => $category->id,
    ]);

    $this->actingAs($admin, 'platform_admin')
        ->patchJson("/admin/catalog/plans/{$plan->id}", [
            'visibility' => 0,
            'affiliate_visibility' => 1,
            'public_visibility' => 0,
            'aff_level_1_max_profit' => 77.5,
            'aff_level_6_max_profit' => 22,
        ])
        ->assertOk()
        ->assertJsonPath('plan.visibility', '0')
        ->assertJsonPath('plan.affiliate_visibility', '1')
        ->assertJsonPath('plan.public_visibility', '0');

    $plan->refresh();
    expect((int) $plan->visibility)->toBe(0)
        ->and((int) $plan->affiliate_visibility)->toBe(1)
        ->and((int) $plan->public_visibility)->toBe(0)
        ->and((float) $plan->aff_level_1_max_profit)->toBe(77.5)
        ->and((float) $plan->aff_level_6_max_profit)->toBe(22.0);
});

it('persists affiliate catalog visibility and six profit levels', function () {
    $admin = platformOperationsAdmin();
    $affiliate = platformOperationsAffiliate();
    $product = Product::create([
        'api_id' => 'affiliate-persistence-product',
        'product_name' => 'Affiliate Persistence Data',
        'slug' => 'affiliate-persistence-data',
    ]);
    $category = ProductPlanCategory::create([
        'api_id' => 'affiliate-persistence-category',
        'product_plan_category_name' => 'Affiliate Persistence SME',
        'product_id' => $product->id,
    ]);
    $globalPlan = ProductPlan::create([
        'product_plan_name' => 'Affiliate Persistence 1GB',
        'product_plan_category_id' => $category->id,
    ]);
    $plan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id,
        'product_plan_id' => $globalPlan->id,
        'product_plan_name' => 'Affiliate Persistence 1GB',
    ]);

    $payload = ['visibility' => 0, 'public_visibility' => 0];
    foreach (range(1, 6) as $level) {
        $payload["user_level_{$level}_profit"] = 40 + $level;
    }

    $this->actingAs($admin, 'platform_admin')
        ->patchJson("/admin/affiliates/{$affiliate->id}/catalog/plans/{$plan->id}", $payload)
        ->assertOk();

    $plan->refresh();
    expect((int) $plan->visibility)->toBe(0)
        ->and((int) $plan->public_visibility)->toBe(0);
    foreach (range(1, 6) as $level) {
        expect((float) $plan->{"user_level_{$level}_profit"})->toBe((float) (40 + $level));
    }
});

it('updates transaction status with an idempotent wallet impact', function () {
    $admin = platformOperationsAdmin();
    $affiliate = platformOperationsAffiliate();
    $role = Role::create(['role_name' => 'User']);
    $user = User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'username' => 'transaction-user',
        'first_name' => 'Transaction', 'last_name' => 'User',
        'email' => 'transaction-user@example.com', 'password' => 'password123',
        'role_id' => $role->id, 'main_wallet' => 100,
    ]);
    $product = Product::create([
        'api_id' => 'transaction-product', 'product_name' => 'Transaction Data', 'slug' => 'transaction-data',
    ]);
    $category = ProductPlanCategory::create([
        'api_id' => 'transaction-category', 'product_plan_category_name' => 'Transaction SME', 'product_id' => $product->id,
    ]);
    $globalPlan = ProductPlan::create([
        'product_plan_name' => 'Transaction 1GB', 'product_plan_category_id' => $category->id,
    ]);
    $affiliatePlan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'product_plan_id' => $globalPlan->id, 'product_plan_name' => 'Transaction 1GB',
    ]);
    $transaction = Transaction::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'api_id' => 'transaction-api-id',
        'txn_reference' => 'transaction-reference', 'affiliate_product_plan_id' => $affiliatePlan->id,
        'user_id' => $user->id, 'status' => -1, 'wallet_category' => 'main_wallet',
        'amount' => 100, 'discounted_amount' => 90, 'balance_before' => 190,
        'balance_after' => 100, 'description' => 'Test transaction',
    ]);

    $url = "/admin/all-transactions/{$transaction->id}/status";
    $payload = ['status' => 2, 'impact_wallet' => true, 'reason' => 'Provider confirmed transaction failure'];

    $this->actingAs($admin, 'platform_admin')->patchJson($url, $payload)
        ->assertOk()->assertJsonPath('wallet_change', 90);
    expect((float) $user->fresh()->main_wallet)->toBe(190.0);

    $this->actingAs($admin, 'platform_admin')->patchJson($url, $payload)
        ->assertOk()->assertJsonPath('wallet_change', 0);
    expect((float) $user->fresh()->main_wallet)->toBe(190.0);

    $this->actingAs($admin, 'platform_admin')->patchJson($url, [
        'status' => 1, 'impact_wallet' => true, 'reason' => 'Provider later confirmed successful delivery',
    ])->assertOk()->assertJsonPath('wallet_change', -90);

    expect((float) $user->fresh()->main_wallet)->toBe(100.0)
        ->and((int) $transaction->fresh()->status)->toBe(1);
});

it('can change transaction status without changing the wallet', function () {
    $admin = platformOperationsAdmin();
    $affiliate = platformOperationsAffiliate();
    $role = Role::create(['role_name' => 'User']);
    $user = User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'username' => 'status-only-user',
        'first_name' => 'Status', 'last_name' => 'Only',
        'email' => 'status-only@example.com', 'password' => 'password123',
        'role_id' => $role->id, 'main_wallet' => 500,
    ]);
    $product = Product::create(['api_id' => 'status-product', 'product_name' => 'Status Data', 'slug' => 'status-data']);
    $category = ProductPlanCategory::create(['api_id' => 'status-category', 'product_plan_category_name' => 'Status SME', 'product_id' => $product->id]);
    $globalPlan = ProductPlan::create(['product_plan_name' => 'Status 1GB', 'product_plan_category_id' => $category->id]);
    $affiliatePlan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create(['affiliate_id' => $affiliate->id, 'product_plan_id' => $globalPlan->id, 'product_plan_name' => 'Status 1GB']);
    $transaction = Transaction::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'api_id' => 'status-api-id',
        'txn_reference' => 'status-reference', 'affiliate_product_plan_id' => $affiliatePlan->id,
        'user_id' => $user->id, 'status' => 0, 'wallet_category' => 'main_wallet',
        'amount' => 200, 'balance_before' => 700, 'balance_after' => 500, 'description' => 'Status-only test',
    ]);

    $this->actingAs($admin, 'platform_admin')
        ->patchJson("/admin/all-transactions/{$transaction->id}/status", [
            'status' => -1, 'impact_wallet' => false, 'reason' => 'Administrative status correction only',
        ])
        ->assertOk()
        ->assertJsonPath('wallet_change', 0);

    expect((float) $user->fresh()->main_wallet)->toBe(500.0)
        ->and((int) $transaction->fresh()->status)->toBe(-1);
});

it('creates a short-lived tenant-bound impersonation handoff', function () {
    $admin = platformOperationsAdmin();
    $affiliate = platformOperationsAffiliate([
        'activation_status' => 1,
        'domain_url' => 'affiliate-impersonation.test',
    ]);
    $role = Role::create(['role_name' => 'Admin']);
    $user = User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id, 'username' => 'affiliate-admin-target',
        'first_name' => 'Affiliate', 'last_name' => 'Administrator',
        'email' => 'affiliate-admin-target@example.com', 'password' => 'password123',
        'pin' => '123456', 'role_id' => $role->id, 'email_verified_at' => now(),
    ]);

    $url = $this->actingAs($admin, 'platform_admin')
        ->postJson("/admin/affiliates/{$affiliate->id}/management-users/{$user->id}/impersonate")
        ->assertOk()
        ->assertJsonPath('expires_in_seconds', 120)
        ->json('url');

    expect($url)->toStartWith('http://affiliate-impersonation.test/platform-impersonation/');
    $path = parse_url($url, PHP_URL_PATH);

    $this->withSession(['affiliate' => $affiliate])
        ->withServerVariables(['HTTP_HOST' => 'affiliate-impersonation.test'])
        ->get($path)
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user, 'web');

    $this->withSession(['affiliate' => $affiliate])
        ->withServerVariables(['HTTP_HOST' => 'affiliate-impersonation.test'])
        ->get($path)
        ->assertGone();
});

it('creates a fully configured affiliate user with a six digit pin', function () {
    $admin = platformOperationsAdmin();
    $affiliate = platformOperationsAffiliate();
    Role::create(['role_name' => 'User']);
    $plan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id,
        'user_plan_name' => 'Starter',
        'plan_level' => 1,
        'is_default' => 1,
    ]);

    $this->actingAs($admin, 'platform_admin')
        ->postJson("/admin/affiliates/{$affiliate->id}/users", [
            'first_name' => 'Detailed',
            'last_name' => 'Customer',
            'other_names' => 'Platform',
            'username' => 'detailed-customer',
            'email' => 'detailed-customer@example.com',
            'phone_number' => '08012345678',
            'pin' => '654321',
            'password' => 'StrongPassword1!',
            'password_confirmation' => 'StrongPassword1!',
            'role' => 'User',
            'user_plan_id' => $plan->id,
            'customer_category' => 'pos',
            'customer_landmark' => 'Central Market',
            'account_tier' => 2,
            'default_wallet_setting' => 'main_wallet',
            'active' => 1,
            'email_verified' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('user.user_plan_id', $plan->id);

    $this->assertDatabaseHas('users', [
        'affiliate_id' => $affiliate->id,
        'username' => 'detailed-customer',
        'pin' => '654321',
        'customer_category' => 'pos',
        'customer_landmark' => 'Central Market',
        'account_tier' => 2,
    ]);
});
