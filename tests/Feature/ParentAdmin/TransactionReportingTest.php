<?php

use App\Models\AffiliateUserPlan;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows only the current parents transactions including legacy rows without a parent snapshot', function () {
    [$parent, $admin, $levels] = managedParent('report-parent');
    [$foreign, $foreignAdmin, $foreignLevels] = managedParent('report-foreign');
    $affiliate = unattachedAffiliate('report-affiliate');
    $affiliate->update(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $levels[0]->id]);
    $foreignAffiliate = unattachedAffiliate('report-foreign-affiliate');
    $foreignAffiliate->update(['parent_business_id' => $foreign->id, 'parent_reseller_level_id' => $foreignLevels[0]->id]);
    $role = Role::create(['role_name' => 'User']);
    $plan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create(['affiliate_id' => $affiliate->id, 'user_plan_name' => 'Basic', 'plan_level' => 1]);
    $foreignPlan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create(['affiliate_id' => $foreignAffiliate->id, 'user_plan_name' => 'Basic', 'plan_level' => 1]);
    $user = User::factory()->create(['affiliate_id' => $affiliate->id, 'role_id' => $role->id, 'user_plan_id' => $plan->id]);
    $foreignUser = User::factory()->create(['affiliate_id' => $foreignAffiliate->id, 'role_id' => $role->id, 'user_plan_id' => $foreignPlan->id]);

    $required = ['api_id' => 'test-plan', 'affiliate_product_plan_id' => 1, 'wallet_category' => 'main_wallet', 'balance_before' => 1000, 'balance_after' => 500, 'description' => 'Test purchase'];
    Transaction::withoutGlobalScope('affiliate')->create($required + ['affiliate_id' => $affiliate->id, 'user_id' => $user->id, 'txn_reference' => 'OWN-LEGACY', 'transaction_category' => 'data', 'amount' => 500, 'status' => 1]);
    Transaction::withoutGlobalScope('affiliate')->create($required + ['parent_business_id' => $parent->id, 'affiliate_id' => $affiliate->id, 'user_id' => $user->id, 'txn_reference' => 'OWN-ROUTED', 'transaction_category' => 'airtime', 'amount' => 200, 'status' => 0, 'routing_status' => 'reconciliation_required']);
    Transaction::withoutGlobalScope('affiliate')->create($required + ['parent_business_id' => $foreign->id, 'affiliate_id' => $foreignAffiliate->id, 'user_id' => $foreignUser->id, 'txn_reference' => 'FOREIGN-TXN', 'transaction_category' => 'data', 'amount' => 900, 'status' => 1]);

    $this->actingAs($admin, 'parent_admin')->get('/parent-admin/transactions')
        ->assertOk()->assertSee('OWN-LEGACY')->assertSee('OWN-ROUTED')->assertDontSee('FOREIGN-TXN')
        ->assertSee('Needs reconciliation');
});
