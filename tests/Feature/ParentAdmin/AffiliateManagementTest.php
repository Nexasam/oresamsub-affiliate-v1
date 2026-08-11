<?php

use App\Models\Affiliate;
use App\Models\AffiliateProductPlanCategory;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function managedParent(string $slug): array
{
    $parent = ParentBusiness::create(['name' => ucfirst($slug), 'slug' => $slug]);
    $admin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => "{$slug}@example.test", 'password' => 'password123', 'active' => true]);
    $levels = collect([1, 2])->map(fn ($position) => $parent->resellerLevels()->create(['name' => "Level {$position}", 'position' => $position, 'status' => 'active']));

    return [$parent, $admin, $levels];
}

function unattachedAffiliate(string $slug): Affiliate
{
    static $sequence = 20;
    $sequence++;

    return Affiliate::create([
        'name' => ucfirst($slug), 'slug' => $slug, 'affiliate_plan_id' => 1,
        'ip_address' => "127.0.2.{$sequence}", 'contact_phone' => "08030000{$sequence}",
        'contact_email' => "{$slug}@example.test", 'parent_key' => "key-{$slug}",
        'parent_email' => "parent-{$slug}@example.test",
    ]);
}

it('lets a parent create attach list and relevel only its affiliates', function () {
    [$parent, $admin, $levels] = managedParent('affiliate-owner');
    [$foreign, $foreignAdmin, $foreignLevels] = managedParent('foreign-owner');
    $eligible = unattachedAffiliate('eligible-affiliate');
    $foreignAffiliate = unattachedAffiliate('foreign-affiliate');
    $foreignAffiliate->update(['parent_business_id' => $foreign->id, 'parent_reseller_level_id' => $foreignLevels[0]->id]);

    $this->actingAs($admin, 'parent_admin')->post('/parent-admin/affiliates', [
        'name' => 'New Affiliate', 'slug' => 'new-affiliate', 'contact_email' => 'new@example.test',
        'contact_phone' => '08039999999', 'domain_url' => 'new.example.test',
        'parent_reseller_level_id' => $levels[0]->id,
    ])->assertRedirect('/parent-admin/affiliates');

    $created = Affiliate::where('slug', 'new-affiliate')->sole();
    expect($created->parent_business_id)->toBe($parent->id)
        ->and($created->parent_reseller_level_id)->toBe($levels[0]->id);

    $this->actingAs($admin, 'parent_admin')->post("/parent-admin/affiliates/{$eligible->id}/attach", [
        'parent_reseller_level_id' => $levels[1]->id,
    ])->assertRedirect('/parent-admin/affiliates');

    $this->actingAs($admin, 'parent_admin')->patch("/parent-admin/affiliates/{$eligible->id}/level", [
        'parent_reseller_level_id' => $levels[0]->id,
    ])->assertRedirect('/parent-admin/affiliates');

    $this->actingAs($admin, 'parent_admin')->post("/parent-admin/affiliates/{$foreignAffiliate->id}/attach", [
        'parent_reseller_level_id' => $levels[0]->id,
    ])->assertNotFound();

    $this->actingAs($admin, 'parent_admin')->get('/parent-admin/affiliates')
        ->assertOk()->assertSee('New Affiliate')->assertSee('Eligible-affiliate')->assertDontSee('Foreign-affiliate');
});

it('syncs only parent categories and preserves affiliate display names', function () {
    [$parent, $admin, $levels] = managedParent('category-parent');
    [$foreign] = managedParent('category-foreign');
    $affiliate = unattachedAffiliate('category-affiliate');
    $affiliate->update(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $levels[0]->id]);
    $product = Product::create(['api_id' => 'sync-data', 'product_name' => 'Data', 'slug' => 'data']);
    $ownedCategory = ProductPlanCategory::create(['api_id' => 'owned-category', 'product_id' => $product->id, 'product_plan_category_name' => 'SME']);
    $foreignCategory = ProductPlanCategory::create(['api_id' => 'foreign-category', 'product_id' => $product->id, 'product_plan_category_name' => 'Gifting']);
    ProductPlan::create(['parent_business_id' => $parent->id, 'product_plan_category_id' => $ownedCategory->id, 'product_plan_name' => 'Owned 1GB']);
    ProductPlan::create(['parent_business_id' => $foreign->id, 'product_plan_category_id' => $foreignCategory->id, 'product_plan_name' => 'Foreign 1GB']);

    $this->actingAs($admin, 'parent_admin')->post("/parent-admin/affiliates/{$affiliate->id}/categories/sync")
        ->assertRedirect('/parent-admin/affiliates');
    $local = AffiliateProductPlanCategory::withoutGlobalScope('affiliate')->sole();
    $local->update(['product_plan_category_name' => 'My Cheap SME']);

    $this->actingAs($admin, 'parent_admin')->post("/parent-admin/affiliates/{$affiliate->id}/categories/sync")
        ->assertRedirect('/parent-admin/affiliates');

    expect(AffiliateProductPlanCategory::withoutGlobalScope('affiliate')->count())->toBe(1)
        ->and($local->fresh()->plan_category_id)->toBe($ownedCategory->id)
        ->and($local->fresh()->product_plan_category_name)->toBe('My Cheap SME');
});

it('lets only the owning parent edit affiliate details and reseller level', function () {
    [$parent, $admin, $levels] = managedParent('editing-parent');
    [$foreign, $foreignAdmin] = managedParent('editing-foreign');
    $affiliate = unattachedAffiliate('editable-affiliate');
    $affiliate->update([
        'parent_business_id' => $parent->id,
        'parent_reseller_level_id' => $levels[0]->id,
    ]);

    $this->actingAs($admin, 'parent_admin')
        ->get("/parent-admin/affiliates/{$affiliate->id}/edit")
        ->assertOk()
        ->assertSee('Edit affiliate')
        ->assertSee('Editable-affiliate');

    $this->actingAs($admin, 'parent_admin')
        ->put("/parent-admin/affiliates/{$affiliate->id}", [
            'name' => 'Updated Affiliate',
            'slug' => 'updated-affiliate',
            'contact_email' => 'updated@example.test',
            'contact_phone' => '08031112222',
            'domain_url' => 'https://updated.example.test',
            'parent_reseller_level_id' => $levels[1]->id,
        ])->assertRedirect('/parent-admin/affiliates');

    expect($affiliate->fresh())
        ->name->toBe('Updated Affiliate')
        ->slug->toBe('updated-affiliate')
        ->contact_email->toBe('updated@example.test')
        ->contact_phone->toBe('08031112222')
        ->domain_url->toBe('https://updated.example.test')
        ->parent_reseller_level_id->toBe($levels[1]->id)
        ->parent_business_id->toBe($parent->id);

    $this->actingAs($foreignAdmin, 'parent_admin')
        ->get("/parent-admin/affiliates/{$affiliate->id}/edit")
        ->assertNotFound();

    $this->actingAs($foreignAdmin, 'parent_admin')
        ->put("/parent-admin/affiliates/{$affiliate->id}", [
            'name' => 'Stolen Affiliate',
            'slug' => 'stolen-affiliate',
            'contact_email' => 'stolen@example.test',
            'contact_phone' => '08032223333',
            'parent_reseller_level_id' => $levels[0]->id,
        ])->assertNotFound();
});
