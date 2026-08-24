<?php

use App\Models\Admin;
use App\Models\Affiliate;
use App\Models\AffiliateOnboardingRequest;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function onboardingFixture(): array
{
    $parent = ParentBusiness::create(['name' => 'Pilot Parent', 'slug' => 'pilot-parent']);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $parentAdmin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => 'pilot-owner@example.test', 'password' => 'password', 'active' => true]);
    $platformAdmin = Admin::create(['name' => 'Platform', 'email' => 'platform-approval@example.test', 'password' => 'password', 'active' => true]);

    return compact('parent', 'level', 'parentAdmin', 'platformAdmin');
}

it('submits new affiliate onboarding for platform approval without creating an affiliate', function () {
    $fixture = onboardingFixture();

    $this->actingAs($fixture['parentAdmin'], 'parent_admin')->post('/parent-admin/affiliates', [
        'name' => 'Requested Affiliate', 'slug' => 'requested-affiliate',
        'contact_email' => 'requested@example.test', 'contact_phone' => '08035550111',
        'domain_url' => 'requested.example.test', 'parent_reseller_level_id' => $fixture['level']->id,
    ])->assertRedirect('/parent-admin/affiliates');

    expect(Affiliate::where('slug', 'requested-affiliate')->exists())->toBeFalse();
    $this->assertDatabaseHas('affiliate_onboarding_requests', [
        'parent_business_id' => $fixture['parent']->id, 'request_type' => 'create',
        'requested_slug' => 'requested-affiliate', 'status' => 'pending',
    ]);
});

it('submits attachment for approval and only attaches after platform approval', function () {
    $fixture = onboardingFixture();
    $affiliate = Affiliate::create(['name' => 'Existing Affiliate', 'slug' => 'existing-affiliate', 'affiliate_plan_id' => 1, 'ip_address' => '127.15.0.1', 'contact_phone' => '08035550112', 'contact_email' => 'existing@example.test', 'parent_key' => 'existing-key', 'parent_email' => 'existing-parent@example.test']);

    $this->actingAs($fixture['parentAdmin'], 'parent_admin')->post("/parent-admin/affiliates/{$affiliate->id}/attach", [
        'parent_reseller_level_id' => $fixture['level']->id,
    ])->assertRedirect('/parent-admin/affiliates');
    expect($affiliate->fresh()->parent_business_id)->toBeNull();

    $onboarding = AffiliateOnboardingRequest::sole();
    $this->actingAs($fixture['platformAdmin'], 'platform_admin')
        ->patchJson("/admin/affiliate-onboarding/{$onboarding->id}/review", ['action' => 'approve'])
        ->assertOk()->assertJsonPath('request.status', 'approved');

    expect($affiliate->fresh()->parent_business_id)->toBe($fixture['parent']->id)
        ->and($affiliate->fresh()->parent_reseller_level_id)->toBe($fixture['level']->id);
});

it('creates a requested affiliate atomically on approval and records rejection reasons', function () {
    $fixture = onboardingFixture();
    $request = AffiliateOnboardingRequest::create([
        'parent_business_id' => $fixture['parent']->id, 'requested_by_parent_admin_id' => $fixture['parentAdmin']->id,
        'parent_reseller_level_id' => $fixture['level']->id, 'request_type' => 'create',
        'requested_name' => 'Approved Affiliate', 'requested_slug' => 'approved-affiliate',
        'requested_email' => 'approved@example.test', 'requested_phone' => '08035550113', 'status' => 'pending',
    ]);

    $this->actingAs($fixture['platformAdmin'], 'platform_admin')
        ->patchJson("/admin/affiliate-onboarding/{$request->id}/review", ['action' => 'approve'])
        ->assertOk();
    $created = Affiliate::where('slug', 'approved-affiliate')->sole();
    expect($created->parent_business_id)->toBe($fixture['parent']->id)
        ->and($request->fresh()->affiliate_id)->toBe($created->id)
        ->and($created->processingProfile?->management_mode)->toBe('parent_managed')
        ->and($created->processingProfile?->processing_engine)->toBe('multi_parent')
        ->and($created->processingProfile?->status)->toBe('active');

    $rejected = AffiliateOnboardingRequest::create([
        'parent_business_id' => $fixture['parent']->id, 'requested_by_parent_admin_id' => $fixture['parentAdmin']->id,
        'parent_reseller_level_id' => $fixture['level']->id, 'request_type' => 'create',
        'requested_name' => 'Rejected Affiliate', 'requested_slug' => 'rejected-affiliate',
        'requested_email' => 'rejected@example.test', 'requested_phone' => '08035550114', 'status' => 'pending',
    ]);
    $this->actingAs($fixture['platformAdmin'], 'platform_admin')
        ->patchJson("/admin/affiliate-onboarding/{$rejected->id}/review", ['action' => 'reject', 'reason' => 'Affiliate licence payment is not confirmed.'])
        ->assertOk();

    expect($rejected->fresh()->status)->toBe('rejected')
        ->and($rejected->fresh()->rejection_reason)->toBe('Affiliate licence payment is not confirmed.')
        ->and(Affiliate::where('slug', 'rejected-affiliate')->exists())->toBeFalse();
});
