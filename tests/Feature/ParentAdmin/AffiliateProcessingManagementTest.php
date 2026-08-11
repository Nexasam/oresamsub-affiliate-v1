<?php

use App\Models\Admin;
use App\Models\Affiliate;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ProviderConnection;

it('lets a parent request affiliate managed processing and platform approve it', function () {
    $parent = ParentBusiness::create(['name' => 'Processing Parent', 'slug' => 'processing-ui']);
    $parentAdmin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => 'processing-ui-parent@example.test', 'password' => 'password', 'active' => true]);
    $platformAdmin = Admin::create(['name' => 'Platform', 'email' => 'processing-ui-platform@example.test', 'password' => 'password', 'active' => true]);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id, 'name' => 'Processing Child', 'slug' => 'processing-ui-child', 'affiliate_plan_id' => 1, 'ip_address' => 'processing-ui', 'contact_phone' => '08300000001', 'contact_email' => 'processing-ui@example.test', 'parent_key' => 'processing-ui-key', 'parent_email' => 'processing-ui-parent-account@example.test']);
    $adapter = ProviderConnection::create(['name' => 'Parent API', 'slug' => 'processing-ui-api', 'adapter' => 'configurable_http', 'status' => 'active']);
    $connection = $parent->providerConnections()->create(['provider_connection_id' => $adapter->id, 'name' => 'Affiliate API', 'status' => 'active', 'approval_status' => 'approved']);

    $this->actingAs($platformAdmin, 'platform_admin')->get('/admin/affiliate-processing')
        ->assertOk()->assertSee('Processing approvals');

    $this->actingAs($parentAdmin, 'parent_admin')
        ->post("/parent-admin/affiliates/{$affiliate->id}/processing/change-requests", [
            'management_mode' => 'affiliate_managed', 'processing_engine' => 'multi_parent',
            'parent_provider_connection_id' => $connection->id,
            'credentials' => ['api_public_key' => 'issued-affiliate-key'],
        ])->assertSessionHasNoErrors()->assertRedirect();

    $change = $affiliate->processingProfile->changeRequests()->sole();
    expect($affiliate->processingProfile->management_mode)->toBe('parent_managed')
        ->and($change->status)->toBe('pending');

    $this->actingAs($platformAdmin, 'platform_admin')
        ->patch("/admin/affiliate-processing/{$change->id}/review", ['decision' => 'approved'])
        ->assertSessionHasNoErrors()->assertRedirect();

    expect($affiliate->processingProfile->fresh()->management_mode)->toBe('affiliate_managed')
        ->and($change->fresh()->status)->toBe('approved');
});
