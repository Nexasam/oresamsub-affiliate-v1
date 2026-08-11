<?php

use App\Models\Affiliate;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;

function settlementParentWorkspace(string $suffix): array
{
    $parent = ParentBusiness::create(['name' => "Workspace {$suffix}", 'slug' => "workspace-{$suffix}"]);
    $admin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => "workspace-{$suffix}@example.test", 'password' => 'password', 'active' => true]);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create([
        'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id,
        'name' => "Child {$suffix}", 'slug' => "child-{$suffix}", 'affiliate_plan_id' => 1,
        'ip_address' => "workspace-{$suffix}", 'contact_phone' => "081{$suffix}",
        'contact_email' => "child-{$suffix}@example.test", 'parent_key' => "workspace-key-{$suffix}",
        'parent_email' => "workspace-parent-{$suffix}@example.test",
    ]);

    return compact('parent', 'admin', 'affiliate');
}

it('lets a parent view and fund an owned affiliate settlement wallet', function () {
    ['admin' => $admin, 'affiliate' => $affiliate] = settlementParentWorkspace('2001');

    $this->actingAs($admin, 'parent_admin')
        ->get("/parent-admin/affiliates/{$affiliate->id}/settlement-wallet")
        ->assertOk()
        ->assertSee('Settlement wallet')
        ->assertSee('Parent managed')
        ->assertSee('Request processing change')
        ->assertSee('₦0.00');

    $this->actingAs($admin, 'parent_admin')
        ->post("/parent-admin/affiliates/{$affiliate->id}/settlement-wallet/credits", [
            'amount' => '2500.50',
            'reference' => 'BANK-2001',
            'reason' => 'Verified transfer from affiliate',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect("/parent-admin/affiliates/{$affiliate->id}/settlement-wallet");

    $this->assertDatabaseHas('affiliate_settlement_wallets', [
        'affiliate_id' => $affiliate->id,
        'available_balance' => '2500.50',
    ]);
});

it('shows the settlement wallet workspace in the parent sidebar', function () {
    ['admin' => $admin, 'affiliate' => $affiliate] = settlementParentWorkspace('2005');

    $this->actingAs($admin, 'parent_admin')
        ->get('/parent-admin/settlement-wallets')
        ->assertOk()
        ->assertSee('Settlement wallets')
        ->assertSee($affiliate->name);
});

it('does not expose another parents settlement wallet', function () {
    ['admin' => $admin] = settlementParentWorkspace('2002');
    ['affiliate' => $otherAffiliate] = settlementParentWorkspace('2003');

    $this->actingAs($admin, 'parent_admin')
        ->get("/parent-admin/affiliates/{$otherAffiliate->id}/settlement-wallet")
        ->assertNotFound();

    $this->actingAs($admin, 'parent_admin')
        ->post("/parent-admin/affiliates/{$otherAffiliate->id}/settlement-wallet/credits", [
            'amount' => '100', 'reference' => 'INVALID-2002', 'reason' => 'Cross-parent attempt',
        ])->assertNotFound();
});

it('validates settlement credits before changing the balance', function () {
    ['admin' => $admin, 'affiliate' => $affiliate] = settlementParentWorkspace('2004');

    $this->actingAs($admin, 'parent_admin')
        ->from("/parent-admin/affiliates/{$affiliate->id}/settlement-wallet")
        ->post("/parent-admin/affiliates/{$affiliate->id}/settlement-wallet/credits", [
            'amount' => '10.999', 'reference' => '', 'reason' => 'x',
        ])
        ->assertSessionHasErrors(['amount', 'reference', 'reason']);

    expect($affiliate->fresh()->settlementWallet)->toBeNull();
});
