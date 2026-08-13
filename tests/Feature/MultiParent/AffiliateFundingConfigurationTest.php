<?php

use App\Models\Affiliate;
use App\Models\AffiliateFundingProviderConfig;
use App\Models\FundingProvider;
use App\Models\ParentBusiness;
use App\Models\ParentFundingProvider;
use App\Models\ParentFundingProviderBank;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('lets an affiliate admin save own credentials and request an approved mode switch', function () {
    $parent = ParentBusiness::create(['name' => 'Affiliate Funding Parent', 'slug' => 'affiliate-funding-parent']);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id, 'name' => 'Funding Child', 'slug' => 'affiliate-funding-child', 'affiliate_plan_id' => 1, 'ip_address' => '127.10.0.1', 'contact_phone' => '08100000001', 'contact_email' => 'affiliate-funding@example.test', 'parent_key' => 'affiliate-funding-key', 'parent_email' => 'parent-funding@example.test']);
    $role = Role::create(['role_name' => 'Admin']);
    $admin = User::create(['affiliate_id' => $affiliate->id, 'role_id' => $role->id, 'username' => 'funding-admin', 'first_name' => 'Funding', 'last_name' => 'Admin', 'email' => 'affiliate-admin@example.test', 'email_verified_at' => now(), 'password' => 'password', 'pin' => '123456']);
    $provider = FundingProvider::create(['name' => 'Xixapay', 'slug' => 'xixapay', 'adapter_key' => 'xixapay', 'credential_fields' => ['api_key'], 'active' => true]);
    $parentProvider = ParentFundingProvider::create(['parent_business_id' => $parent->id, 'funding_provider_id' => $provider->id, 'credentials' => ['api_key' => 'parent-secret'], 'active' => true, 'generation_enabled' => true]);
    $config = AffiliateFundingProviderConfig::create(['affiliate_id' => $affiliate->id, 'parent_funding_provider_id' => $parentProvider->id, 'management_mode' => 'parent_managed', 'active' => true, 'generation_enabled' => true]);
    $bank = ParentFundingProviderBank::create(['parent_funding_provider_id' => $parentProvider->id, 'name' => '9PSB', 'bank_code' => '9PSB', 'rate_type' => 'flat', 'rate_value' => 50, 'active' => true, 'generation_enabled' => true]);

    $this->withSession(['affiliate' => $affiliate])->actingAs($admin)
        ->get('/admin/affiliate-funding-providers')->assertOk()->assertSee('Affiliate funding providers')->assertDontSee('parent-secret')
        ->assertSee('Switch to affiliate-managed funding')->assertDontSee('Request switch to parent managed');

    $this->withSession(['affiliate' => $affiliate])->actingAs($admin)
        ->put("/admin/affiliate-funding-providers/{$config->id}", [
            'credentials' => ['api_key' => 'affiliate-secret'], 'webhook_secret' => 'webhook-secret', 'webhook_active' => '1',
            'banks' => [[
                'parent_funding_provider_bank_id' => $bank->id, 'rate_type' => 'percentage',
                'rate_value' => '1.5', 'percentage_cap' => '100', 'active' => '1', 'generation_enabled' => '1',
            ]],
        ])->assertRedirect('/admin/affiliate-funding-providers');

    expect(DB::table('affiliate_funding_provider_configs')->where('id', $config->id)->value('credentials'))->not->toContain('affiliate-secret')
        ->and(DB::table('affiliate_funding_provider_configs')->where('id', $config->id)->value('webhook_secret'))->not->toContain('webhook-secret')
        ->and($config->banks()->sole()->parent_funding_provider_bank_id)->toBe($bank->id)
        ->and($config->banks()->sole()->rate_type)->toBe('percentage')
        ->and($config->banks()->sole()->percentage_cap)->toBe('100.00');

    $this->withSession(['affiliate' => $affiliate])->actingAs($admin)
        ->post("/admin/affiliate-funding-providers/{$config->id}/mode-request", ['requested_mode' => 'affiliate_managed'])
        ->assertRedirect('/admin/affiliate-funding-providers');

    expect($config->fresh()->management_mode)->toBe('parent_managed')
        ->and($config->modeChangeRequests()->sole()->status)->toBe('pending');
});
