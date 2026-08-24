<?php

use App\Models\Affiliate;
use App\Models\ParentAdmin;
use App\Models\User;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function customerUiAffiliate(array $attributes = []): Affiliate
{
    return Affiliate::create(array_merge([
        'name' => 'UI Test Affiliate',
        'slug' => 'ui-test-affiliate',
        'domain_url' => 'ui-test.example.test',
        'affiliate_plan_id' => 1,
        'ip_address' => '127.0.0.1',
        'contact_phone' => '08010000000',
        'contact_email' => 'ui@example.test',
        'parent_key' => 'ui-test',
        'parent_email' => 'parent-ui@example.test',
    ], $attributes));
}

function customerUiUser(Affiliate $affiliate, array $attributes = []): User
{
    $planId = DB::table('affiliate_user_plans')->insertGetId([
        'affiliate_id' => $affiliate->id,
        'user_plan_name' => 'Basic',
        'plan_level' => 1,
        'is_default' => 1,
        'visibility' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return User::factory()->create(array_merge([
        'affiliate_id' => $affiliate->id,
        'user_plan_id' => $planId,
    ], $attributes));
}

test('an authenticated customer can switch to interface v2', function () {
    config()->set('customer-ui.v2_enabled', true);
    config()->set('customer-ui.force_v1', false);

    $affiliate = customerUiAffiliate();
    $user = customerUiUser($affiliate);

    $this->actingAs($user)
        ->patch(route('customer-ui.update'), ['version' => 'v2'])
        ->assertRedirect();

    expect($user->fresh()->customer_ui_version)->toBe('v2');
});

test('interface v2 cannot be selected while the rollout is disabled', function () {
    config()->set('customer-ui.v2_enabled', false);

    $affiliate = customerUiAffiliate();
    $user = customerUiUser($affiliate);

    $this->actingAs($user)
        ->from('/profile')
        ->patch(route('customer-ui.update'), ['version' => 'v2'])
        ->assertSessionHasErrors('version');

    expect($user->fresh()->customer_ui_version)->toBeNull();
});

test('the resolver honours user preference affiliate default and force v1', function () {
    config()->set('customer-ui.v2_enabled', true);
    config()->set('customer-ui.force_v1', false);

    $affiliate = customerUiAffiliate(['customer_ui_default' => 'v2']);
    $user = customerUiUser($affiliate, ['customer_ui_version' => null]);
    $resolver = app(\App\Services\CustomerUiResolver::class);

    expect($resolver->resolve($user, $affiliate))->toBe('v2');

    $user->customer_ui_version = 'v1';
    expect($resolver->resolve($user, $affiliate))->toBe('v1');

    config()->set('customer-ui.force_v1', true);
    $user->customer_ui_version = 'v2';
    expect($resolver->resolve($user, $affiliate))->toBe('v1');
});

test('guests cannot change the customer interface', function () {
    $this->patch(route('customer-ui.update'), ['version' => 'v2'])
        ->assertRedirect(route('login'));
});

test('shared inertia data ignores a parent administrator for customer ui resolution', function () {
    config()->set('customer-ui.v2_enabled', true);
    config()->set('customer-ui.force_v1', false);

    $request = Request::create('/parent-admin', 'GET');
    $request->setUserResolver(fn () => new ParentAdmin([
        'id' => 2,
        'name' => 'Parent Owner',
        'email' => 'parent@example.test',
    ]));

    $shared = app(HandleInertiaRequests::class)->share($request);

    expect($shared['customerUi']['version'])->toBe('v1');
});
