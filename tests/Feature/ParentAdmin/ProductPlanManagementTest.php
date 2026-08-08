<?php

use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
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
        ->assertSee('Next page');
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
            'visibility' => true,
            'affiliate_visibility' => true,
            'public_visibility' => false,
        ])->assertCreated()
        ->assertJsonPath('plan.parent_business_id', $parent->id);

    $plan = ProductPlan::sole();
    expect($plan->parent_business_id)->toBe($parent->id)
        ->and($plan->cost_price)->toBe('250.5')
        ->and($plan->public_visibility)->toBe('0');
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
        ])->assertOk()
        ->assertJsonPath('plan.product_plan_name', 'After');

    $this->actingAs($admin, 'parent_admin')
        ->patchJson("/parent-admin/product-plans/{$foreign->id}", ['product_plan_name' => 'Corrupted'])
        ->assertNotFound();

    expect($owned->fresh()->profit_category)->toBe('percent')
        ->and($foreign->fresh()->product_plan_name)->toBe('Foreign before');
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
