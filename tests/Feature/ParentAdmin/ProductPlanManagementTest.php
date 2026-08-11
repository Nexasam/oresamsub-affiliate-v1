<?php

use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Models\ProviderConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function catalogParent(string $slug): array
{
    $parent = ParentBusiness::create(['name' => ucfirst($slug), 'slug' => $slug]);
    $admin = ParentAdmin::create([
        'parent_business_id' => $parent->id,
        'name' => ucfirst($slug).' Owner',
        'email' => "{$slug}@example.test",
        'password' => 'secret-password',
        'active' => true,
    ]);

    return [$parent, $admin];
}

function catalogCategory(): ProductPlanCategory
{
    $product = Product::create([
        'api_id' => 'parent-catalog-product',
        'product_name' => 'Data',
        'slug' => 'parent-catalog-data',
    ]);

    return ProductPlanCategory::create([
        'api_id' => 'parent-catalog-category',
        'product_id' => $product->id,
        'product_plan_category_name' => 'SME',
    ]);
}

function catalogRouting(ParentBusiness $parent): ParentProviderConnection
{
    $adapter = ProviderConnection::create([
        'name' => "{$parent->slug} HTTP",
        'slug' => "{$parent->slug}-http",
        'adapter' => 'configurable_http',
        'capabilities' => ['services' => ['data'], 'methods' => ['POST']],
        'status' => 'active',
    ]);

    return ParentProviderConnection::create([
        'parent_business_id' => $parent->id,
        'provider_connection_id' => $adapter->id,
        'name' => 'Primary provider',
        'settings' => ['is_primary' => true],
        'status' => 'active',
        'approval_status' => 'approved',
    ]);
}

function catalogLevels(ParentBusiness $parent, int $count = 2): array
{
    return collect(range(1, $count))->map(fn (int $position) => $parent->resellerLevels()->create([
        'name' => "Level {$position}", 'position' => $position, 'status' => 'active',
    ]))->all();
}

function comprehensivePlanPayload(ProductPlanCategory $category, ParentProviderConnection $connection, array $levels, array $overrides = []): array
{
    return array_replace_recursive([
        'product_plan_name' => 'MTN SME 1GB',
        'product_plan_category_id' => $category->id,
        'api_id' => 'PARENT-DATA-1GB',
        'admin_cost_price' => 210,
        'cost_price' => 200,
        'data_size_in_mb' => 1024,
        'validity_in_days' => 30,
        'profit_category' => 'flat',
        'commission_feature' => true,
        'upline_commission_option' => 'flat',
        'upline_flat_commission' => 5,
        'upline_percentage_commission' => 0,
        'upline_commission_cap' => 1000,
        'visibility' => true,
        'affiliate_visibility' => true,
        'public_visibility' => true,
        'route' => [
            'parent_provider_connection_id' => $connection->id,
            'provider_plan_id' => 'PAULTECHS-MTN-1GB',
        ],
        'prices' => collect($levels)->map(fn ($level, int $index) => [
            'parent_reseller_level_id' => $level->id,
            'selling_price' => 220 + ($index * 10),
            'max_profit' => 70,
        ])->all(),
    ], $overrides);
}

it('renders a functional parent product plan workspace', function () {
    [, $admin] = catalogParent('workspace-parent');

    $this->actingAs($admin, 'parent_admin')
        ->get('/parent-admin')
        ->assertOk()
        ->assertSee('Open product plans')
        ->assertSee('Open pricing');

    $this->actingAs($admin, 'parent_admin')
        ->get('/parent-admin/product-plans')
        ->assertOk()
        ->assertSee('Manage product plans')
        ->assertSee('Add product plan')
        ->assertSee('Bulk addition')
        ->assertSee('Provider external plan ID')
        ->assertSee('Reseller acquisition prices')
        ->assertSee('Parent product plans');
});

it('uses blade forms instead of alpine requests for product plan creation', function () {
    [, $admin] = catalogParent('blade-plan-parent');
    $category = catalogCategory();

    $this->actingAs($admin, 'parent_admin')
        ->get('/parent-admin/product-plans')
        ->assertOk()
        ->assertSee('<form method="POST"', false)
        ->assertDontSee('@submit.prevent="createPlan"', false)
        ->assertDontSee('axios.post(this.urls.store', false)
        ->assertDontSee('x-init="load()"', false);

    $this->actingAs($admin, 'parent_admin')->post('/parent-admin/product-plans', [
        'product_plan_name' => 'Blade draft plan',
        'product_plan_category_id' => $category->id,
        'cost_price' => 100,
        'profit_category' => 'flat',
        'visibility' => false,
        'affiliate_visibility' => false,
        'public_visibility' => false,
    ])->assertRedirect(route('parent-admin.product-plans.index'))
        ->assertSessionHas('success', 'Product plan added.');
});

it('lists only plans owned by the authenticated parent', function () {
    [$firstParent, $admin] = catalogParent('first-parent');
    [$secondParent] = catalogParent('second-parent');
    $category = catalogCategory();

    ProductPlan::create(['parent_business_id' => $firstParent->id, 'product_plan_category_id' => $category->id, 'product_plan_name' => 'Visible plan']);
    ProductPlan::create(['parent_business_id' => $secondParent->id, 'product_plan_category_id' => $category->id, 'product_plan_name' => 'Foreign plan']);

    $response = $this->actingAs($admin, 'parent_admin')
        ->getJson('/parent-admin/product-plans/data')
        ->assertOk();

    expect($response->json('plans.data'))->toHaveCount(1)
        ->and($response->json('plans.data.0.product_plan_name'))->toBe('Visible plan')
        ->and($response->json('categories.0.id'))->toBe($category->id);
});

it('creates a plan for the authenticated parent and ignores submitted ownership', function () {
    [$parent, $admin] = catalogParent('creating-parent');
    [$foreignParent] = catalogParent('foreign-owner');
    $category = catalogCategory();

    $this->actingAs($admin, 'parent_admin')
        ->postJson('/parent-admin/product-plans', [
            'parent_business_id' => $foreignParent->id,
            'product_plan_name' => 'Parent 1GB',
            'product_plan_category_id' => $category->id,
            'cost_price' => 250.50,
            'profit_category' => 'flat',
            'visibility' => false,
            'affiliate_visibility' => false,
            'public_visibility' => false,
        ])->assertCreated()
        ->assertJsonPath('plan.parent_business_id', $parent->id);

    $plan = ProductPlan::sole();
    expect($plan->parent_business_id)->toBe($parent->id)
        ->and($plan->cost_price)->toBe('250.5')
        ->and($plan->public_visibility)->toBe('0');
});

it('creates a comprehensive active plan with its primary route and every reseller price', function () {
    [$parent, $admin] = catalogParent('comprehensive-parent');
    $category = catalogCategory();
    $connection = catalogRouting($parent);
    $levels = catalogLevels($parent);

    $this->actingAs($admin, 'parent_admin')
        ->postJson('/parent-admin/product-plans', comprehensivePlanPayload($category, $connection, $levels))
        ->assertCreated()
        ->assertJsonPath('plan.provider_routes.0.provider_plan_id', 'PAULTECHS-MTN-1GB')
        ->assertJsonCount(2, 'plan.parent_prices');

    $plan = ProductPlan::sole();
    expect($plan->parent_business_id)->toBe($parent->id)
        ->and($plan->api_id)->toBe('PARENT-DATA-1GB')
        ->and($plan->data_size_in_mb)->toBe('1024')
        ->and($plan->validity_in_days)->toBe('30')
        ->and($plan->providerRoutes()->sole()->parent_provider_connection_id)->toBe($connection->id)
        ->and($plan->parentPrices()->count())->toBe(2);
});

it('rejects an active external parent plan without approved same-parent routing and complete prices', function () {
    [$parent, $admin] = catalogParent('guarded-plan-parent');
    [$foreignParent] = catalogParent('foreign-route-parent');
    $category = catalogCategory();
    $foreignConnection = catalogRouting($foreignParent);
    $levels = catalogLevels($parent);

    $this->actingAs($admin, 'parent_admin')->postJson('/parent-admin/product-plans', [
        ...comprehensivePlanPayload($category, $foreignConnection, $levels),
        'prices' => [['parent_reseller_level_id' => $levels[0]->id, 'selling_price' => 220]],
    ])->assertUnprocessable()->assertJsonValidationErrors(['route.parent_provider_connection_id', 'prices']);

    expect(ProductPlan::count())->toBe(0);
});

it('allows an external parent to save an unrouted plan as a hidden draft', function () {
    [$parent, $admin] = catalogParent('draft-plan-parent');
    $category = catalogCategory();

    $this->actingAs($admin, 'parent_admin')->postJson('/parent-admin/product-plans', [
        'product_plan_name' => 'Draft plan',
        'product_plan_category_id' => $category->id,
        'cost_price' => 100,
        'profit_category' => 'flat',
        'visibility' => false,
        'affiliate_visibility' => false,
        'public_visibility' => false,
    ])->assertCreated();

    expect($parent->productPlans()->sole()->providerRoutes()->count())->toBe(0);
});

it('bulk creates complete parent plans atomically', function () {
    [$parent, $admin] = catalogParent('bulk-plan-parent');
    $category = catalogCategory();
    $connection = catalogRouting($parent);
    $levels = catalogLevels($parent);
    $first = comprehensivePlanPayload($category, $connection, $levels);
    $second = comprehensivePlanPayload($category, $connection, $levels, [
        'product_plan_name' => 'MTN SME 2GB',
        'api_id' => 'PARENT-DATA-2GB',
        'route' => ['provider_plan_id' => 'PAULTECHS-MTN-2GB'],
    ]);

    $this->actingAs($admin, 'parent_admin')->postJson('/parent-admin/product-plans/bulk', [
        'plans' => [$first, $second],
    ])->assertCreated()->assertJsonPath('created_count', 2);

    expect($parent->productPlans()->count())->toBe(2)
        ->and($parent->productPlans()->whereHas('providerRoutes')->count())->toBe(2)
        ->and($parent->productPlans()->withCount('parentPrices')->get()->pluck('parent_prices_count')->all())->toBe([2, 2]);
});

it('rolls back every row when one bulk plan is invalid', function () {
    [$parent, $admin] = catalogParent('atomic-bulk-parent');
    $category = catalogCategory();
    $connection = catalogRouting($parent);
    $levels = catalogLevels($parent);
    $valid = comprehensivePlanPayload($category, $connection, $levels);
    $invalid = comprehensivePlanPayload($category, $connection, $levels, ['product_plan_name' => '']);

    $this->actingAs($admin, 'parent_admin')->postJson('/parent-admin/product-plans/bulk', [
        'plans' => [$valid, $invalid],
    ])->assertUnprocessable()->assertJsonValidationErrors('plans.1.product_plan_name');

    expect(ProductPlan::count())->toBe(0);
});

it('updates only plans belonging to the authenticated parent', function () {
    [$parent, $admin] = catalogParent('editing-parent');
    [$foreignParent] = catalogParent('blocked-parent');
    $category = catalogCategory();
    $owned = ProductPlan::create(['parent_business_id' => $parent->id, 'product_plan_category_id' => $category->id, 'product_plan_name' => 'Before']);
    $foreign = ProductPlan::create(['parent_business_id' => $foreignParent->id, 'product_plan_category_id' => $category->id, 'product_plan_name' => 'Foreign before']);

    $this->actingAs($admin, 'parent_admin')
        ->patchJson("/parent-admin/product-plans/{$owned->id}", [
            'product_plan_name' => 'After',
            'cost_price' => 300,
            'profit_category' => 'percent',
            'visibility' => false,
            'affiliate_visibility' => false,
            'public_visibility' => false,
        ])->assertOk()
        ->assertJsonPath('plan.product_plan_name', 'After');

    $this->actingAs($admin, 'parent_admin')
        ->patchJson("/parent-admin/product-plans/{$foreign->id}", ['product_plan_name' => 'Corrupted'])
        ->assertNotFound();

    expect($owned->fresh()->profit_category)->toBe('percent')
        ->and($foreign->fresh()->product_plan_name)->toBe('Foreign before');
});

it('does not activate an external parent draft without a valid route and complete prices', function () {
    [$parent, $admin] = catalogParent('draft-activation-parent');
    $category = catalogCategory();
    catalogLevels($parent);
    $draft = ProductPlan::create([
        'parent_business_id' => $parent->id,
        'product_plan_category_id' => $category->id,
        'product_plan_name' => 'Unrouted draft',
        'cost_price' => 100,
        'visibility' => false,
        'affiliate_visibility' => false,
        'public_visibility' => false,
    ]);

    $this->actingAs($admin, 'parent_admin')
        ->patchJson("/parent-admin/product-plans/{$draft->id}", [
            'visibility' => true,
            'affiliate_visibility' => true,
            'public_visibility' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('visibility');

    expect((bool) $draft->fresh()->visibility)->toBeFalse();
});

it('validates product plan fields without creating partial records', function () {
    [, $admin] = catalogParent('validating-parent');

    $this->actingAs($admin, 'parent_admin')
        ->postJson('/parent-admin/product-plans', [
            'product_plan_name' => '',
            'product_plan_category_id' => 999999,
            'cost_price' => -1,
            'profit_category' => 'invalid',
        ])->assertUnprocessable()
        ->assertJsonValidationErrors(['product_plan_name', 'product_plan_category_id', 'cost_price', 'profit_category']);

    expect(ProductPlan::count())->toBe(0);
});
