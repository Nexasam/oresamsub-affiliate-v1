<?php

use App\Models\Admin;
use App\Models\Affiliate;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ProviderConnection;
use App\Services\AffiliateProcessingProfileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

function processingAffiliate(ParentBusiness $parent, string $suffix): Affiliate
{
    $level = $parent->resellerLevels()->firstOrCreate(['position' => 1], ['name' => 'Basic', 'status' => 'active']);

    return Affiliate::create([
        'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id,
        'name' => "Affiliate {$suffix}", 'slug' => "processing-{$suffix}", 'affiliate_plan_id' => 1,
        'ip_address' => "processing-{$suffix}", 'contact_phone' => "082{$suffix}",
        'contact_email' => "processing-{$suffix}@example.test", 'parent_key' => "processing-key-{$suffix}",
        'parent_email' => "processing-parent-{$suffix}@example.test",
    ]);
}

it('defaults a new parents affiliate to parent managed multi parent processing', function () {
    $parent = ParentBusiness::create(['name' => 'Paul Techs', 'slug' => 'paul-techs']);
    $affiliate = processingAffiliate($parent, '3001');

    $profile = app(AffiliateProcessingProfileService::class)->ensure($affiliate);

    expect($profile->management_mode)->toBe('parent_managed')
        ->and($profile->processing_engine)->toBe('multi_parent')
        ->and($profile->status)->toBe('active');
});

it('preserves existing oresamsub affiliates as legacy affiliate managed', function () {
    $parent = ParentBusiness::create(['name' => 'OresamSub', 'slug' => 'oresamsub']);
    $affiliate = processingAffiliate($parent, '3002');

    $profile = app(AffiliateProcessingProfileService::class)->ensure($affiliate);

    expect($profile->management_mode)->toBe('affiliate_managed')
        ->and($profile->processing_engine)->toBe('legacy_oresamsub');
});

it('stages generic affiliate managed credentials until platform approval', function () {
    $parent = ParentBusiness::create(['name' => 'Paul Techs', 'slug' => 'paul-techs-approval']);
    $affiliate = processingAffiliate($parent, '3003');
    $parentAdmin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => 'processing-owner@example.test', 'password' => 'password', 'active' => true]);
    $adapter = ProviderConnection::create(['name' => 'Paul API', 'slug' => 'paul-api', 'adapter' => 'configurable_http', 'status' => 'active']);
    $connection = $parent->providerConnections()->create(['provider_connection_id' => $adapter->id, 'name' => 'Affiliate API', 'status' => 'active', 'approval_status' => 'approved']);
    $service = app(AffiliateProcessingProfileService::class);
    $profile = $service->ensure($affiliate);

    $request = $service->requestChange($affiliate, $parentAdmin, [
        'management_mode' => 'affiliate_managed',
        'processing_engine' => 'multi_parent',
        'parent_provider_connection_id' => $connection->id,
        'credentials' => ['api_public_key' => 'affiliate-secret-key'],
    ]);

    expect($profile->fresh()->management_mode)->toBe('parent_managed')
        ->and($request->status)->toBe('pending')
        ->and(DB::table('affiliate_processing_change_requests')->where('id', $request->id)->value('credentials'))->not->toContain('affiliate-secret-key');

    $platformAdmin = Admin::create(['name' => 'Platform', 'email' => 'processing-platform@example.test', 'password' => 'password']);
    $service->approve($request, $platformAdmin);
    $profile = $profile->fresh();

    expect($profile->management_mode)->toBe('affiliate_managed')
        ->and($profile->processing_engine)->toBe('multi_parent')
        ->and($profile->parent_provider_connection_id)->toBe($connection->id)
        ->and($profile->credentials['api_public_key'])->toBe('affiliate-secret-key')
        ->and($request->fresh()->status)->toBe('approved');
});

it('rejects affiliate managed connections owned by another parent', function () {
    $parent = ParentBusiness::create(['name' => 'First', 'slug' => 'processing-first']);
    $other = ParentBusiness::create(['name' => 'Second', 'slug' => 'processing-second']);
    $affiliate = processingAffiliate($parent, '3004');
    $parentAdmin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => 'processing-first@example.test', 'password' => 'password', 'active' => true]);
    $adapter = ProviderConnection::create(['name' => 'Foreign API', 'slug' => 'foreign-api', 'adapter' => 'configurable_http', 'status' => 'active']);
    $foreignConnection = $other->providerConnections()->create(['provider_connection_id' => $adapter->id, 'name' => 'Foreign', 'status' => 'active', 'approval_status' => 'approved']);

    expect(fn () => app(AffiliateProcessingProfileService::class)->requestChange($affiliate, $parentAdmin, [
        'management_mode' => 'affiliate_managed', 'processing_engine' => 'multi_parent',
        'parent_provider_connection_id' => $foreignConnection->id, 'credentials' => ['api_public_key' => 'x'],
    ]))->toThrow(ValidationException::class);
});
