<?php

use App\Models\Admin;
use App\Models\ProviderRoutingRollout;
use App\Services\Providers\ProviderRoutingRolloutService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires the environment kill switch and an explicit parent or affiliate service rollout', function () {
    $fixture = executableParentPurchase();
    config(['parent_businesses.features.provider_routing' => false]);

    expect(app(ProviderRoutingRolloutService::class)->enabledFor($fixture['affiliatePlan']))->toBeFalse();

    ProviderRoutingRollout::create([
        'parent_business_id' => $fixture['parent']->id,
        'scope_type' => 'parent',
        'scope_id' => $fixture['parent']->id,
        'service' => 'data',
        'enabled' => true,
    ]);
    config(['parent_businesses.features.provider_routing' => true]);

    expect(app(ProviderRoutingRolloutService::class)->enabledFor($fixture['affiliatePlan']))->toBeTrue();

    ProviderRoutingRollout::create([
        'parent_business_id' => $fixture['parent']->id,
        'scope_type' => 'affiliate',
        'scope_id' => $fixture['affiliate']->id,
        'service' => 'data',
        'enabled' => false,
    ]);

    expect(app(ProviderRoutingRolloutService::class)->enabledFor($fixture['affiliatePlan']))->toBeFalse();
});

it('allows only platform admins to change a tenant-bound routing rollout', function () {
    $fixture = executableParentPurchase();
    $admin = Admin::create(['name' => 'Platform', 'email' => 'routing-admin@example.test', 'password' => 'password', 'active' => true]);

    $this->put('/admin/provider-routing-rollouts', [])->assertRedirect('/admin/login');
    $this->actingAs($admin, 'platform_admin')->put('/admin/provider-routing-rollouts', [
        'parent_business_id' => $fixture['parent']->id,
        'scope_type' => 'affiliate',
        'scope_id' => $fixture['affiliate']->id,
        'service' => 'data',
        'enabled' => true,
    ])->assertRedirect();

    $this->assertDatabaseHas('provider_routing_rollouts', [
        'parent_business_id' => $fixture['parent']->id,
        'scope_type' => 'affiliate',
        'scope_id' => $fixture['affiliate']->id,
        'service' => 'data',
        'enabled' => true,
    ]);
});
