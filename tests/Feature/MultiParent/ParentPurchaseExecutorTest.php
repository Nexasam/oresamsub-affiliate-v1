<?php

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use App\Models\ProviderConnection;
use App\Services\Providers\ConfigurableProviderClient;
use App\Services\Providers\ParentPurchaseExecutor;
use App\Services\Providers\PurchaseRouteResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

function executableParentPurchase(): array
{
    $parent = ParentBusiness::create(['name' => 'Executor Parent', 'slug' => 'executor-parent']);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create([
        'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id,
        'name' => 'Executor Affiliate', 'slug' => 'executor-affiliate', 'affiliate_plan_id' => 1,
        'ip_address' => '127.8.0.1', 'contact_phone' => '08050000031',
        'contact_email' => 'executor@example.test', 'parent_key' => 'executor-key',
        'parent_email' => 'executor-parent@example.test',
    ]);
    $adapter = ProviderConnection::create(['name' => 'HTTP', 'slug' => 'executor-http', 'adapter' => 'configurable_http', 'capabilities' => ['services' => ['data']], 'status' => 'active']);
    $connection = ParentProviderConnection::create(['parent_business_id' => $parent->id, 'provider_connection_id' => $adapter->id, 'name' => 'Primary', 'status' => 'active', 'approval_status' => 'approved']);
    $product = Product::create(['api_id' => 'executor-data', 'product_name' => 'Data', 'slug' => 'data']);
    $category = ProductPlanCategory::create(['api_id' => 'executor-category', 'product_id' => $product->id, 'product_plan_category_name' => 'SME']);
    $plan = ProductPlan::create(['parent_business_id' => $parent->id, 'product_plan_category_id' => $category->id, 'product_plan_name' => '1GB', 'visibility' => 1, 'affiliate_visibility' => 1]);
    $route = $plan->providerRoutes()->create(['parent_business_id' => $parent->id, 'parent_provider_connection_id' => $connection->id, 'provider_plan_id' => 'PAUL-1GB', 'priority' => 1, 'active' => true]);
    $affiliatePlan = AffiliateProductPlan::withoutGlobalScope('affiliate')->create(['affiliate_id' => $affiliate->id, 'product_plan_id' => $plan->id, 'product_plan_name' => '1GB', 'visibility' => 1, 'visibility_from_admin' => 1]);

    return compact('parent', 'affiliate', 'adapter', 'connection', 'plan', 'route', 'affiliatePlan');
}

it('executes a resolved parent route with its provider plan reference and returns a transaction snapshot', function () {
    $fixture = executableParentPurchase();
    $affiliatePlan = $fixture['affiliatePlan'];

    $this->mock(ConfigurableProviderClient::class, function (MockInterface $mock) {
        $mock->shouldReceive('execute')->once()->withArgs(function ($connection, $service, $runtime) {
            return $service === 'data'
                && $runtime['plan'] === 'PAUL-1GB'
                && $runtime['provider_plan_id'] === 'PAUL-1GB'
                && $runtime['reference'] === 'ORDER-100';
        })->andReturn([
            'successful' => true,
            'ambiguous' => false,
            'message' => 'Delivered',
            'provider_reference' => 'PAUL-REF-1',
            'http_status' => 200,
            'provider_response' => ['status' => 'success'],
        ]);
    });

    $result = app(ParentPurchaseExecutor::class)->execute($affiliatePlan, [
        'phone_number' => '08030000000',
        'plan' => 'OLD-INTERNAL-ID',
        'reference' => 'ORDER-100',
    ]);

    expect($result)->toMatchArray([
        'status' => 1,
        'ambiguous' => false,
        'user_message' => 'Delivered',
        'provider_reference' => 'PAUL-REF-1',
        'parent_business_id' => $fixture['parent']->id,
        'provider_plan_id_snapshot' => 'PAUL-1GB',
        'routing_status' => 'successful',
    ]);
});

it('keeps timeouts pending for reconciliation and marks conclusive failures refundable', function (bool $ambiguous, int $status, string $routingStatus) {
    $fixture = executableParentPurchase();

    $this->mock(PurchaseRouteResolver::class, fn (MockInterface $mock) => $mock
        ->shouldReceive('resolve')->once()->andReturn([
            'affiliate' => $fixture['affiliate'], 'parent' => $fixture['parent'],
            'affiliate_product_plan' => $fixture['affiliatePlan'], 'product_plan' => $fixture['plan'],
            'route' => $fixture['route'], 'connection' => $fixture['connection'],
            'adapter' => $fixture['adapter'], 'provider_plan_id' => 'PAUL-1GB', 'product_slug' => 'data',
        ]));
    $this->mock(ConfigurableProviderClient::class, fn (MockInterface $mock) => $mock
        ->shouldReceive('execute')->once()->andReturn([
            'successful' => false, 'ambiguous' => $ambiguous, 'message' => 'Provider issue',
            'provider_reference' => null, 'http_status' => null, 'provider_response' => null,
        ]));

    $result = app(ParentPurchaseExecutor::class)->execute($fixture['affiliatePlan'], ['reference' => 'ORDER-101']);

    expect($result['status'])->toBe($status)
        ->and($result['routing_status'])->toBe($routingStatus)
        ->and($result['requires_reconciliation'])->toBe($ambiguous)
        ->and($result['refundable'])->toBe(! $ambiguous);
})->with([
    'ambiguous timeout' => [true, 0, 'reconciliation_required'],
    'conclusive failure' => [false, 2, 'failed'],
]);
