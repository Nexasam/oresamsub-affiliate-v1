<?php

use App\Models\Affiliate;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function profitReportFixture(string $slug): array
{
    $parent = ParentBusiness::create(['name' => "{$slug} Parent", 'slug' => "{$slug}-parent"]);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id, 'name' => "{$slug} Affiliate", 'slug' => "{$slug}-affiliate", 'affiliate_plan_id' => 1, 'ip_address' => '127.30.'.strlen($slug).'.'.$parent->id, 'contact_phone' => '0801000000'.$parent->id, 'contact_email' => "{$slug}@example.test", 'parent_key' => "{$slug}-key", 'parent_email' => "parent-{$slug}@example.test"]);
    $userPlan = DB::table('affiliate_user_plans')->insertGetId(['affiliate_id' => $affiliate->id, 'user_plan_name' => 'Basic', 'plan_level' => 1, 'is_default' => 1, 'visibility' => 1, 'created_at' => now(), 'updated_at' => now()]);
    $userRole = Role::firstOrCreate(['role_name' => 'User']);
    $customer = User::factory()->create(['affiliate_id' => $affiliate->id, 'user_plan_id' => $userPlan, 'role_id' => $userRole->id]);
    return compact('parent', 'affiliate', 'customer');
}

function createProfitTransaction(array $f, string $reference, string $routing = 'successful'): Transaction
{
    return Transaction::withoutGlobalScope('affiliate')->create([
        'parent_business_id' => $f['parent']->id, 'affiliate_id' => $f['affiliate']->id, 'user_id' => $f['customer']->id,
        'api_id' => 'PLAN-1', 'affiliate_product_plan_id' => 1, 'txn_reference' => $reference,
        'transaction_category' => 'data', 'wallet_category' => 'main_wallet', 'amount' => '130.00',
        'balance_before' => '1000.00', 'balance_after' => '870.00', 'description' => 'Profit test',
        'status' => $routing === 'successful' ? 1 : 0, 'routing_status' => $routing,
        'provider_cost_snapshot' => '100.00', 'parent_cost_snapshot' => '100.00',
        'affiliate_cost_snapshot' => '120.00', 'customer_price_snapshot' => '130.00',
        'parent_profit_snapshot' => '20.00', 'affiliate_profit_snapshot' => '10.00',
    ]);
}

it('shows only realised tenant profit to the parent admin and exports it', function () {
    $own = profitReportFixture('profit-own');
    $foreign = profitReportFixture('profit-foreign');
    createProfitTransaction($own, 'PROFIT-OWN-SUCCESS');
    createProfitTransaction($own, 'PROFIT-OWN-PENDING', 'reconciliation_required');
    createProfitTransaction($foreign, 'PROFIT-FOREIGN');
    $admin = ParentAdmin::create(['parent_business_id' => $own['parent']->id, 'name' => 'Owner', 'email' => 'profit-owner@example.test', 'password' => 'password', 'active' => true]);

    $this->actingAs($admin, 'parent_admin')->get('/parent-admin/profits')->assertOk()
        ->assertSee('Affiliate charges')->assertSee('Affiliate charge')->assertSee('Provider cost')
        ->assertSee('₦120.00')->assertSee('₦100.00')->assertSee('₦20.00')->assertDontSee('₦130.00')
        ->assertSee('PROFIT-OWN-SUCCESS')->assertDontSee('PROFIT-OWN-PENDING')->assertDontSee('PROFIT-FOREIGN');
    $this->actingAs($admin, 'parent_admin')->get('/parent-admin/profits/export')
        ->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('shows only realised profit belonging to the affiliate admin', function () {
    $own = profitReportFixture('affiliate-profit-own');
    $foreign = profitReportFixture('affiliate-profit-foreign');
    createProfitTransaction($own, 'AFFILIATE-PROFIT-SUCCESS');
    createProfitTransaction($own, 'AFFILIATE-PROFIT-FAILED', 'failed');
    createProfitTransaction($foreign, 'AFFILIATE-PROFIT-FOREIGN');
    $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);
    $admin = User::factory()->create(['affiliate_id' => $own['affiliate']->id, 'user_plan_id' => $own['customer']->user_plan_id, 'role_id' => $adminRole->id]);

    $this->actingAs($admin)->withSession(['affiliate' => $own['affiliate']])->get('/admin/profits')->assertOk()
        ->assertSee('Customer sales')->assertSee('Customer sale')->assertSee('Acquisition cost')
        ->assertSee('₦130.00')->assertSee('₦120.00')->assertSee('₦10.00')
        ->assertSee('AFFILIATE-PROFIT-SUCCESS')->assertDontSee('AFFILIATE-PROFIT-FAILED')->assertDontSee('AFFILIATE-PROFIT-FOREIGN');
});
