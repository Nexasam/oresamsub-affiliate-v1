<?php

use App\Models\Affiliate;
use App\Models\AffiliateFundingProviderConfig;
use App\Models\FundingModeChangeRequest;
use App\Models\FundingProvider;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('stacks funding providers in one full-width row each', function () {
    $parent = ParentBusiness::create(['name' => 'Funding Parent', 'slug' => 'funding-layout']);
    $admin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => 'funding-layout@example.test', 'password' => 'password', 'active' => true]);
    FundingProvider::create(['name' => 'Xixapay', 'slug' => 'xixapay', 'adapter_key' => 'xixapay', 'active' => true]);
    FundingProvider::create(['name' => 'SecurewaveNG', 'slug' => 'securewaveng', 'adapter_key' => 'securewaveng', 'active' => true]);

    $this->actingAs($admin, 'parent_admin')
        ->get('/parent-admin/funding-providers')
        ->assertOk()
        ->assertSee('Xixapay')
        ->assertSee('SecurewaveNG')
        ->assertDontSee('xl:grid-cols-2', false);
});

it('saves an affiliate configuration from the funding affiliates table', function () {
    $parent = ParentBusiness::create(['name' => 'Funding Parent', 'slug' => 'funding-save']);
    $admin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => 'funding-save@example.test', 'password' => 'password', 'active' => true]);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id, 'name' => 'Child', 'slug' => 'funding-save-child', 'affiliate_plan_id' => 1, 'ip_address' => '127.9.0.2', 'contact_phone' => '08090000002', 'contact_email' => 'save-child@example.test', 'parent_key' => 'save-child-key', 'parent_email' => 'parent@example.test']);
    $provider = FundingProvider::create(['name' => 'Xixapay', 'slug' => 'xixapay', 'adapter_key' => 'xixapay', 'active' => true]);
    $parentProvider = $parent->fundingProviders()->create(['funding_provider_id' => $provider->id, 'active' => true, 'generation_enabled' => true]);

    $this->actingAs($admin, 'parent_admin')
        ->put("/parent-admin/funding-providers/{$parentProvider->id}/affiliates/{$affiliate->id}", [
            'management_mode' => 'affiliate_managed',
            'active' => '1',
            'generation_enabled' => '1',
            'bank_codes' => [''],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect("/parent-admin/funding-providers/{$parentProvider->id}/affiliates");

    $this->assertDatabaseHas('affiliate_funding_provider_configs', [
        'affiliate_id' => $affiliate->id,
        'parent_funding_provider_id' => $parentProvider->id,
        'management_mode' => 'affiliate_managed',
        'active' => 1,
        'generation_enabled' => 1,
    ]);
});

it('lets a parent enable providers configure credentials and approve affiliate mode changes', function () {
    $parent = ParentBusiness::create(['name' => 'Funding Parent', 'slug' => 'funding-workspace']);
    $admin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => 'funding-parent@example.test', 'password' => 'password', 'active' => true]);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id, 'name' => 'Child', 'slug' => 'funding-child', 'affiliate_plan_id' => 1, 'ip_address' => '127.9.0.1', 'contact_phone' => '08090000001', 'contact_email' => 'child@example.test', 'parent_key' => 'child-key', 'parent_email' => 'parent@example.test']);
    $provider = FundingProvider::create(['name' => 'Xixapay', 'slug' => 'xixapay', 'adapter_key' => 'xixapay', 'credential_fields' => ['api_key', 'secret_key'], 'active' => true]);

    $this->actingAs($admin, 'parent_admin')->post("/parent-admin/funding-providers/{$provider->id}/enable", [
        'credentials' => ['api_key' => 'parent-key', 'secret_key' => 'parent-secret'],
        'webhook_secret' => 'parent-webhook-secret', 'webhook_active' => '1',
        'active' => '1', 'generation_enabled' => '1',
    ])->assertRedirect('/parent-admin/funding-providers');

    $parentProvider = $parent->fundingProviders()->sole();
    expect(DB::table('parent_funding_providers')->where('id', $parentProvider->id)->value('credentials'))->not->toContain('parent-secret');

    $this->actingAs($admin, 'parent_admin')->post("/parent-admin/funding-providers/{$parentProvider->id}/banks", [
        'name' => '9PSB', 'bank_code' => '9PSB', 'rate_type' => 'percentage', 'rate_value' => '1.5',
        'percentage_cap' => '100', 'active' => '1', 'generation_enabled' => '1',
    ])->assertRedirect();
    expect($parentProvider->banks()->sole()->percentage_cap)->toBe('100.00');

    $this->actingAs($admin, 'parent_admin')->get("/parent-admin/funding-providers/{$parentProvider->id}/banks")
        ->assertOk()->assertSee('9PSB')->assertSee('Percentage');

    $this->actingAs($admin, 'parent_admin')->put("/parent-admin/funding-providers/{$parentProvider->id}/affiliates/{$affiliate->id}", [
        'management_mode' => 'affiliate_managed', 'active' => '1', 'generation_enabled' => '1',
        'bank_codes' => ['9PSB', 'SAFEHAVEN'],
    ])->assertRedirect("/parent-admin/funding-providers/{$parentProvider->id}/affiliates");

    $config = AffiliateFundingProviderConfig::sole();
    FundingModeChangeRequest::create(['affiliate_funding_provider_config_id' => $config->id, 'requested_mode' => 'affiliate_managed', 'status' => 'pending']);

    $this->actingAs($admin, 'parent_admin')->patch("/parent-admin/funding-mode-requests/{$config->modeChangeRequests()->sole()->id}", [
        'decision' => 'approved',
    ])->assertRedirect('/parent-admin/funding-providers');

    expect($config->fresh()->management_mode)->toBe('affiliate_managed')
        ->and($config->modeChangeRequests()->sole()->status)->toBe('approved');

    $this->actingAs($admin, 'parent_admin')->get("/parent-admin/funding-providers/{$parentProvider->id}/affiliates?q=Child")
        ->assertOk()->assertSee('Child')->assertSee('Affiliate managed');
});

it('hides and rejects parent-managed customer funding for new affiliate configurations', function () {
    $parent = ParentBusiness::create(['name' => 'Safe Funding Parent', 'slug' => 'safe-funding']);
    $admin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => 'safe-funding@example.test', 'password' => 'password', 'active' => true]);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id, 'name' => 'Child', 'slug' => 'safe-funding-child', 'affiliate_plan_id' => 1, 'ip_address' => '127.9.0.9', 'contact_phone' => '08090000009', 'contact_email' => 'safe-child@example.test', 'parent_key' => 'safe-child-key', 'parent_email' => 'parent@example.test']);
    $provider = FundingProvider::create(['name' => 'Xixapay', 'slug' => 'xixapay-safe', 'adapter_key' => 'xixapay_safe', 'active' => true]);
    $parentProvider = $parent->fundingProviders()->create(['funding_provider_id' => $provider->id, 'active' => true, 'generation_enabled' => true]);

    $this->actingAs($admin, 'parent_admin')->get("/parent-admin/funding-providers/{$parentProvider->id}/affiliates")
        ->assertOk()->assertSee('Affiliate managed')->assertDontSee('Parent managed');

    $this->actingAs($admin, 'parent_admin')->put("/parent-admin/funding-providers/{$parentProvider->id}/affiliates/{$affiliate->id}", [
        'management_mode' => 'parent_managed', 'active' => '1', 'generation_enabled' => '1',
    ])->assertSessionHasErrors('management_mode');
});
