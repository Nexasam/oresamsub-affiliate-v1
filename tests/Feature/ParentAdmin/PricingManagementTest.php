<?php

use App\Models\Affiliate;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ParentResellerLevel;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Models\ProductPlanParentPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function pricingContext(string $slug = 'pricing-parent'): array
{
    $parent = ParentBusiness::create(['name' => ucfirst($slug), 'slug' => $slug]);
    $admin = ParentAdmin::create([
        'parent_business_id' => $parent->id,
        'name' => 'Pricing Owner',
        'email' => "{$slug}@example.test",
        'password' => 'secret-password',
        'active' => true,
    ]);
    $product = Product::create(['api_id' => "{$slug}-product", 'product_name' => 'Data', 'slug' => "{$slug}-data"]);
    $category = ProductPlanCategory::create(['api_id' => "{$slug}-category", 'product_id' => $product->id, 'product_plan_category_name' => 'SME']);
    $plan = ProductPlan::create([
        'parent_business_id' => $parent->id,
        'product_plan_category_id' => $category->id,
        'product_plan_name' => '1GB',
        'cost_price' => 100,
    ]);

    return [$parent, $admin, $plan];
}

it('renders the parent pricing workspace and parent scoped data', function () {
    [$parent, $admin, $plan] = pricingContext();
    ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Starter', 'position' => 1]);
    [$foreignParent] = pricingContext('pricing-foreign');
    ParentResellerLevel::create(['parent_business_id' => $foreignParent->id, 'name' => 'Foreign', 'position' => 1]);

    $this->actingAs($admin, 'parent_admin')->get('/parent-admin/pricing')
        ->assertOk()->assertSee('Manage reseller pricing')->assertSee('Complete six levels')
        ->assertSee('Next page')->assertSee('Default profit settings')
        ->assertSee('Search plans')->assertSee('Pricing status')
        ->assertSee('Using default')->assertSee('Use default');

    $response = $this->actingAs($admin, 'parent_admin')->getJson('/parent-admin/pricing/data')->assertOk();
    expect($response->json('levels'))->toHaveCount(1)
        ->and($response->json('levels.0.name'))->toBe('Starter')
        ->and($response->json('plans.data'))->toHaveCount(1)
        ->and($response->json('plans.data.0.id'))->toBe($plan->id);
});

it('filters the complete parent pricing catalogue before pagination', function () {
    [$parent, $admin] = pricingContext('filter-parent');
    $category = $parent->productPlans()->first()->product_plan_category_id;
    foreach (range(1, 55) as $number) {
        ProductPlan::create([
            'parent_business_id' => $parent->id,
            'product_plan_category_id' => $category,
            'product_plan_name' => $number === 55 ? 'Special MTN plan' : "Ordinary {$number}",
            'cost_price' => 100,
        ]);
    }

    $this->actingAs($admin, 'parent_admin')->getJson('/parent-admin/pricing/data?search=Special')
        ->assertOk()
        ->assertJsonPath('plans.total', 1)
        ->assertJsonPath('plans.data.0.product_plan_name', 'Special MTN plan');
});

it('removes a plan price override so the plan resumes default inheritance', function () {
    [$parent, $admin, $plan] = pricingContext('clear-override');
    $level = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Basic', 'position' => 1]);
    ProductPlanParentPrice::create([
        'parent_business_id' => $parent->id,
        'product_plan_id' => $plan->id,
        'parent_reseller_level_id' => $level->id,
        'selling_price' => 150,
    ]);

    $this->actingAs($admin, 'parent_admin')
        ->deleteJson("/parent-admin/pricing/plans/{$plan->id}/levels/{$level->id}")
        ->assertOk()->assertJsonPath('message', 'Plan now uses the default profit setting.');

    expect(ProductPlanParentPrice::count())->toBe(0);
});

it('can save a mixed matrix without converting inherited levels into overrides', function () {
    [$parent, $admin, $plan] = pricingContext('mixed-pricing');
    $basic = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Basic', 'position' => 1]);
    $gold = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Gold', 'position' => 2]);

    $this->actingAs($admin, 'parent_admin')->putJson("/parent-admin/pricing/plans/{$plan->id}", ['prices' => [
        ['parent_reseller_level_id' => $basic->id, 'selling_price' => null, 'inherit' => true],
        ['parent_reseller_level_id' => $gold->id, 'selling_price' => 130, 'max_profit' => 25, 'inherit' => false],
    ]])->assertOk();

    expect(ProductPlanParentPrice::where('product_plan_id', $plan->id)->count())->toBe(1)
        ->and(ProductPlanParentPrice::where('product_plan_id', $plan->id)->value('parent_reseller_level_id'))->toBe($gold->id);
});

it('manages a contiguous set of one through six reseller levels', function () {
    [$parent, $admin] = pricingContext();
    $first = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Old basic', 'position' => 1]);
    ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Old gold', 'position' => 2]);

    $this->actingAs($admin, 'parent_admin')->putJson('/parent-admin/pricing/levels', [
        'levels' => [['id' => $first->id, 'position' => 1, 'name' => 'Starter']],
    ])->assertOk()->assertJsonPath('levels.0.name', 'Starter');

    expect(ParentResellerLevel::where('parent_business_id', $parent->id)->where('status', 'active')->count())->toBe(1)
        ->and(ParentResellerLevel::where('parent_business_id', $parent->id)->where('position', 2)->value('status'))->toBe('inactive');
});

it('generates only missing default levels up to six', function () {
    [$parent, $admin] = pricingContext();
    ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'My entry level', 'position' => 1]);

    $this->actingAs($admin, 'parent_admin')->postJson('/parent-admin/pricing/levels/generate-six')
        ->assertOk()->assertJsonPath('created', 5);
    $this->actingAs($admin, 'parent_admin')->postJson('/parent-admin/pricing/levels/generate-six')
        ->assertOk()->assertJsonPath('created', 0);

    expect(ParentResellerLevel::where('parent_business_id', $parent->id)->orderBy('position')->pluck('name')->all())
        ->toBe(['My entry level', 'Bronze', 'Silver', 'Gold', 'Diamond', 'Platinum']);
});

it('rejects invalid or foreign reseller level sets without partial updates', function () {
    [$parent, $admin] = pricingContext();
    $owned = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Owned', 'position' => 1]);
    [$foreignParent] = pricingContext('level-foreign');
    $foreign = ParentResellerLevel::create(['parent_business_id' => $foreignParent->id, 'name' => 'Foreign', 'position' => 1]);

    $this->actingAs($admin, 'parent_admin')->putJson('/parent-admin/pricing/levels', [
        'levels' => [
            ['id' => $owned->id, 'position' => 1, 'name' => 'Changed'],
            ['id' => $foreign->id, 'position' => 2, 'name' => 'Stolen'],
        ],
    ])->assertUnprocessable();

    expect($owned->fresh()->name)->toBe('Owned')
        ->and($foreign->fresh()->name)->toBe('Foreign');

    $this->actingAs($admin, 'parent_admin')->putJson('/parent-admin/pricing/levels', ['levels' => []])->assertUnprocessable();
    $this->actingAs($admin, 'parent_admin')->putJson('/parent-admin/pricing/levels', [
        'levels' => collect(range(1, 7))->map(fn ($position) => ['position' => $position, 'name' => "Level {$position}"])->all(),
    ])->assertUnprocessable();
});

it('does not deactivate a reseller level referenced by an affiliate', function () {
    [$parent, $admin] = pricingContext();
    $first = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Basic', 'position' => 1]);
    $second = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Bronze', 'position' => 2]);
    Affiliate::create([
        'name' => 'Attached affiliate', 'slug' => 'attached-affiliate', 'affiliate_plan_id' => 1,
        'ip_address' => '127.0.3.1', 'contact_phone' => '08030000001', 'contact_email' => 'attached@example.test',
        'parent_key' => 'attached-key', 'parent_email' => 'attached-parent@example.test',
        'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $second->id,
    ]);

    $this->actingAs($admin, 'parent_admin')->putJson('/parent-admin/pricing/levels', [
        'levels' => [['id' => $first->id, 'position' => 1, 'name' => 'Basic']],
    ])->assertUnprocessable()->assertJsonValidationErrors('levels');

    expect($second->fresh()->status)->toBe('active');
});

it('rejects removing a middle level because historical positions remain unique', function () {
    [$parent, $admin] = pricingContext();
    $first = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Basic', 'position' => 1]);
    ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Bronze', 'position' => 2]);
    $third = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Silver', 'position' => 3]);

    $this->actingAs($admin, 'parent_admin')->putJson('/parent-admin/pricing/levels', [
        'levels' => [
            ['id' => $first->id, 'position' => 1, 'name' => 'Basic'],
            ['id' => $third->id, 'position' => 2, 'name' => 'Silver'],
        ],
    ])->assertUnprocessable()->assertJsonValidationErrors('levels');

    expect($third->fresh()->position)->toBe(3);
});

it('atomically upserts one normalized price for every active parent level', function () {
    [$parent, $admin, $plan] = pricingContext();
    $basic = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Basic', 'position' => 1]);
    $gold = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Gold', 'position' => 2]);

    $this->actingAs($admin, 'parent_admin')->putJson("/parent-admin/pricing/plans/{$plan->id}", [
        'prices' => [
            ['parent_reseller_level_id' => $basic->id, 'selling_price' => 120.25, 'max_profit' => 30],
            ['parent_reseller_level_id' => $gold->id, 'selling_price' => 115, 'max_profit' => null],
        ],
    ])->assertOk()->assertJsonCount(2, 'prices');

    expect(ProductPlanParentPrice::where('product_plan_id', $plan->id)->count())->toBe(2)
        ->and(ProductPlanParentPrice::where('product_plan_id', $plan->id)->where('parent_reseller_level_id', $basic->id)->value('selling_price'))->toBe('120.25');
});

it('rejects incomplete below-cost and cross-parent prices without changing existing prices', function () {
    [$parent, $admin, $plan] = pricingContext();
    $basic = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Basic', 'position' => 1]);
    $gold = ParentResellerLevel::create(['parent_business_id' => $parent->id, 'name' => 'Gold', 'position' => 2]);
    ProductPlanParentPrice::create([
        'parent_business_id' => $parent->id, 'product_plan_id' => $plan->id,
        'parent_reseller_level_id' => $basic->id, 'selling_price' => 125,
    ]);
    [$foreignParent, , $foreignPlan] = pricingContext('price-foreign');
    $foreignLevel = ParentResellerLevel::create(['parent_business_id' => $foreignParent->id, 'name' => 'Foreign', 'position' => 1]);

    foreach ([
        [['parent_reseller_level_id' => $basic->id, 'selling_price' => 120]],
        [
            ['parent_reseller_level_id' => $basic->id, 'selling_price' => 99],
            ['parent_reseller_level_id' => $gold->id, 'selling_price' => 115],
        ],
        [
            ['parent_reseller_level_id' => $basic->id, 'selling_price' => 120],
            ['parent_reseller_level_id' => $foreignLevel->id, 'selling_price' => 120],
        ],
    ] as $prices) {
        $this->actingAs($admin, 'parent_admin')->putJson("/parent-admin/pricing/plans/{$plan->id}", ['prices' => $prices])->assertUnprocessable();
        expect(ProductPlanParentPrice::where('product_plan_id', $plan->id)->where('parent_reseller_level_id', $basic->id)->value('selling_price'))->toBe('125.00');
    }

    $this->actingAs($admin, 'parent_admin')->putJson("/parent-admin/pricing/plans/{$foreignPlan->id}", [
        'prices' => [['parent_reseller_level_id' => $foreignLevel->id, 'selling_price' => 120]],
    ])->assertNotFound();
});
