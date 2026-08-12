<?php

use App\Models\Role;
use App\Models\User;
use App\Models\AffiliateUserPlan;

it('allows an affiliate admin to impersonate only a customer in its affiliate', function () {
    $f = affiliateProfitFixture('impersonate1');
    $adminRole = Role::create(['role_name' => 'Admin']);
    $userRole = Role::create(['role_name' => 'User']);
    $plan = AffiliateUserPlan::create(['affiliate_id' => $f['affiliate']->id, 'user_plan_name' => 'Basic', 'plan_level' => 1]);
    $admin = User::factory()->create(['affiliate_id' => $f['affiliate']->id, 'role_id' => $adminRole->id, 'user_plan_id' => $plan->id]);
    $customer = User::factory()->create(['affiliate_id' => $f['affiliate']->id, 'role_id' => $userRole->id, 'user_plan_id' => $plan->id]);
    session(['affiliate' => $f['affiliate']]);

    $this->actingAs($admin)->get(route('admin.impersonate', $customer))->assertRedirect(route('dashboard'));
    expect(auth()->id())->toBe($customer->id)->and(session('impersonator'))->toBe($admin->id);
});

it('rejects impersonating a customer from another affiliate', function () {
    $first = affiliateProfitFixture('impersonate2');
    $other = affiliateProfitFixture('impersonate3');
    $adminRole = Role::create(['role_name' => 'Admin']);
    $userRole = Role::create(['role_name' => 'User']);
    $firstPlan = AffiliateUserPlan::create(['affiliate_id' => $first['affiliate']->id, 'user_plan_name' => 'Basic', 'plan_level' => 1]);
    $otherPlan = AffiliateUserPlan::create(['affiliate_id' => $other['affiliate']->id, 'user_plan_name' => 'Basic', 'plan_level' => 1]);
    $admin = User::factory()->create(['affiliate_id' => $first['affiliate']->id, 'role_id' => $adminRole->id, 'user_plan_id' => $firstPlan->id]);
    $customer = User::factory()->create(['affiliate_id' => $other['affiliate']->id, 'role_id' => $userRole->id, 'user_plan_id' => $otherPlan->id]);
    session(['affiliate' => $first['affiliate']]);

    $this->actingAs($admin)->get(route('admin.impersonate', $customer))->assertForbidden();
});
