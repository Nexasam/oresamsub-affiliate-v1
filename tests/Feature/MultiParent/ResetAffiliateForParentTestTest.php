<?php

use App\Models\Affiliate;
use App\Models\ParentBusiness;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function resetCommandAffiliate(): Affiliate
{
    $oresamsub = ParentBusiness::create(['name' => 'OresamSub', 'slug' => 'oresamsub']);
    $level = $oresamsub->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create([
        'parent_business_id' => $oresamsub->id, 'parent_reseller_level_id' => $level->id,
        'name' => 'Old Emiplug', 'slug' => 'old-emiplug', 'affiliate_plan_id' => 1,
        'ip_address' => 'reset-old', 'domain_url' => 'emiplug.name.ng',
        'contact_phone' => '08099990000', 'contact_email' => 'old@emiplug.test',
        'parent_key' => 'old-parent-key', 'parent_email' => 'old-parent@emiplug.test',
    ]);
    $userPlan = DB::table('affiliate_user_plans')->insertGetId(['affiliate_id' => $affiliate->id, 'user_plan_name' => 'Basic', 'plan_level' => 1, 'visibility' => 1, 'created_at' => now(), 'updated_at' => now()]);
    $user = User::factory()->create(['affiliate_id' => $affiliate->id, 'user_plan_id' => $userPlan]);
    $product = DB::table('products')->insertGetId(['api_id' => 'reset-product', 'product_name' => 'Data', 'slug' => 'reset-data', 'created_at' => now(), 'updated_at' => now()]);
    $category = DB::table('product_plan_categories')->insertGetId(['api_id' => 'reset-category', 'product_plan_category_name' => 'Reset', 'product_id' => $product, 'created_at' => now(), 'updated_at' => now()]);
    $plan = DB::table('product_plans')->insertGetId(['parent_business_id' => $oresamsub->id, 'product_plan_category_id' => $category, 'product_plan_name' => 'Reset Plan', 'created_at' => now(), 'updated_at' => now()]);
    $affiliatePlan = DB::table('affiliate_product_plans')->insertGetId(['affiliate_id' => $affiliate->id, 'product_plan_id' => $plan, 'product_plan_name' => 'Reset Plan', 'created_at' => now(), 'updated_at' => now()]);
    Transaction::withoutGlobalScope('affiliate')->create(['affiliate_id' => $affiliate->id, 'api_id' => 'reset', 'affiliate_product_plan_id' => $affiliatePlan, 'user_id' => $user->id, 'wallet_category' => 'main_wallet', 'amount' => 10, 'balance_before' => 20, 'balance_after' => 10, 'description' => 'Old transaction']);

    return $affiliate;
}

it('previews without changing the existing affiliate', function () {
    $affiliate = resetCommandAffiliate();
    $this->artisan('multi-parent:reset-test-affiliate', ['--domain' => 'emiplug.name.ng'])->assertSuccessful();
    expect(Affiliate::find($affiliate->id))->not->toBeNull();
});

it('requires exact confirmation before destructive execution', function () {
    $affiliate = resetCommandAffiliate();
    $this->artisan('multi-parent:reset-test-affiliate', ['--domain' => 'emiplug.name.ng', '--execute' => true, '--confirm-domain' => 'wrong.name.ng'])->assertFailed();
    expect(Affiliate::find($affiliate->id))->not->toBeNull();
});

it('purges the old tenant and recreates the domain under a fresh parent', function () {
    $old = resetCommandAffiliate();
    config(['parent_businesses.reset_test_affiliate.parent_admin_password' => 'StrongPassword123']);

    $this->artisan('multi-parent:reset-test-affiliate', [
        '--domain' => 'emiplug.name.ng', '--execute' => true, '--confirm-domain' => 'emiplug.name.ng',
        '--parent-name' => 'Emiplug Parent', '--parent-slug' => 'emiplug-parent',
        '--parent-admin-name' => 'Emiplug Parent Admin', '--parent-admin-email' => 'parent@emiplug.test',
        '--affiliate-name' => 'Emiplug', '--affiliate-slug' => 'emiplug',
    ])->assertSuccessful();

    $fresh = Affiliate::where('domain_url', 'emiplug.name.ng')->firstOrFail();
    expect($fresh->id)->not->toBe($old->id)
        ->and($fresh->parentBusiness->slug)->toBe('emiplug-parent')
        ->and($fresh->processingProfile->management_mode)->toBe('parent_managed')
        ->and($fresh->processingProfile->processing_engine)->toBe('multi_parent')
        ->and(User::withoutGlobalScope('affiliate')->where('affiliate_id', $old->id)->exists())->toBeFalse()
        ->and(Transaction::withoutGlobalScope('affiliate')->where('affiliate_id', $old->id)->exists())->toBeFalse();
});
