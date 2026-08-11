<?php

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Models\ProviderConnection;
use App\Services\Providers\PurchaseRouteResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function resolvablePurchase(): array
{
    $parent = ParentBusiness::create(['name' => 'Route Parent', 'slug' => 'route-parent']);
    $level = $parent->resellerLevels()->create([
        'name' => 'Basic',
        'position' => 1,
        'status' => 'active',
    ]);
    $affiliate = Affiliate::create([
        'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id,
        'name' => 'Route Affiliate', 'slug' => 'route-affiliate',
        'affiliate_plan_id' => 1, 'ip_address' => '127.4.0.1', 'contact_phone' => '08050000001',
        'contact_email' => 'route-affiliate@example.test', 'parent_key' => 'route-key', 'parent_email' => 'route-parent@example.test',
    ]);
    $adapter = ProviderConnection::create(['name' => 'HTTP', 'slug' => 'route-http', 'adapter' => 'configurable_http', 'capabilities' => ['services' => ['data']], 'status' => 'active']);
    $connection = ParentProviderConnection::create(['parent_business_id' => $parent->id, 'provider_connection_id' => $adapter->id, 'name' => 'Primary', 'status' => 'active', 'approval_status' => 'approved']);
    $product = Product::create(['api_id' => 'route-data', 'product_name' => 'Data', 'slug' => 'data']);
    $category = ProductPlanCategory::create(['api_id' => 'route-category', 'product_id' => $product->id, 'product_plan_category_name' => 'SME']);
    $plan = ProductPlan::create(['parent_business_id' => $parent->id, 'product_plan_category_id' => $category->id, 'product_plan_name' => '1GB', 'visibility' => 1, 'affiliate_visibility' => 1]);
    $route = $plan->providerRoutes()->create(['parent_business_id' => $parent->id, 'parent_provider_connection_id' => $connection->id, 'provider_plan_id' => 'PAUL-1GB', 'priority' => 1, 'active' => true]);
    $affiliatePlan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create(['affiliate_id' => $affiliate->id, 'product_plan_id' => $plan->id, 'product_plan_name' => '1GB', 'visibility' => 1, 'visibility_from_admin' => 1]);

    return compact('parent', 'affiliate', 'adapter', 'connection', 'plan', 'route', 'affiliatePlan');
}

it('resolves an affiliate plan through its parent plan route connection and adapter', function () {
    $fixture = resolvablePurchase();

    $resolved = app(PurchaseRouteResolver::class)->resolve($fixture['affiliatePlan']);

    expect($resolved['affiliate']->is($fixture['affiliate']))->toBeTrue()
        ->and($resolved['parent']->is($fixture['parent']))->toBeTrue()
        ->and($resolved['product_plan']->is($fixture['plan']))->toBeTrue()
        ->and($resolved['route']->is($fixture['route']))->toBeTrue()
        ->and($resolved['connection']->is($fixture['connection']))->toBeTrue()
        ->and($resolved['adapter']->is($fixture['adapter']))->toBeTrue()
        ->and($resolved['provider_plan_id'])->toBe('PAUL-1GB')
        ->and($resolved['product_slug'])->toBe('data');
});

it('fails closed for inactive unapproved or incomplete purchase routes', function (string $failure) {
    $fixture = resolvablePurchase();
    match ($failure) {
        'affiliate plan' => $fixture['affiliatePlan']->update(['visibility' => 0]),
        'plan' => $fixture['plan']->update(['visibility' => 0]),
        'route' => $fixture['route']->update(['active' => false]),
        'connection' => $fixture['connection']->update(['approval_status' => 'pending']),
        'adapter' => $fixture['adapter']->update(['status' => 'inactive']),
    };

    expect(fn () => app(PurchaseRouteResolver::class)->resolve($fixture['affiliatePlan']->fresh()))
        ->toThrow(ValidationException::class);
})->with(['affiliate plan', 'plan', 'route', 'connection', 'adapter']);
