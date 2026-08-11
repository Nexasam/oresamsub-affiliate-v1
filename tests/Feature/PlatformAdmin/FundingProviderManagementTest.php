<?php

use App\Models\Admin;
use App\Models\FundingProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function fundingPlatformAdmin(): Admin
{
    return Admin::create(['name' => 'Funding Admin', 'email' => 'funding-admin@example.test', 'password' => 'password', 'active' => true]);
}

it('lets only platform admins manage the approved funding provider catalogue', function () {
    $this->get('/admin/funding-providers')->assertRedirect('/admin/login');

    $admin = fundingPlatformAdmin();
    $this->actingAs($admin, 'platform_admin')->get('/admin/funding-providers')
        ->assertOk()->assertSee('Funding providers')->assertSee('Xixapay')->assertSee('SecurewaveNG');

    expect(FundingProvider::whereIn('slug', ['xixapay', 'securewaveng'])->count())->toBe(2);

    $this->actingAs($admin, 'platform_admin')->post('/admin/funding-providers', [
        'name' => 'New Pay', 'slug' => 'new-pay', 'adapter_key' => 'new_pay',
        'credential_fields' => 'api_key, secret_key', 'active' => '1',
    ])->assertRedirect('/admin/funding-providers');

    $provider = FundingProvider::where('slug', 'new-pay')->sole();
    expect($provider->credential_fields)->toBe(['api_key', 'secret_key']);
    $this->actingAs($admin, 'platform_admin')->put("/admin/funding-providers/{$provider->id}", [
        'name' => 'New Pay', 'slug' => 'new-pay', 'adapter_key' => 'new_pay',
        'credential_fields' => ['api_key'], 'active' => '0',
    ])->assertRedirect('/admin/funding-providers');

    expect($provider->fresh()->active)->toBeFalse();
});
