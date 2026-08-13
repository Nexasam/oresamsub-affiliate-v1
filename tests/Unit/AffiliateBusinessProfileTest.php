<?php

use App\Models\Affiliate;
use App\Models\AffiliateProcessingProfile;
use Tests\TestCase;

uses(TestCase::class);

it('hides purchase credentials for explicitly parent managed affiliates', function () {
    $affiliate = new Affiliate();
    $affiliate->setRelation('processingProfile', new AffiliateProcessingProfile([
        'management_mode' => 'parent_managed',
        'processing_engine' => 'multi_parent',
    ]));

    expect($affiliate->managesOwnPurchaseCredentials())->toBeFalse();
    expect($affiliate->usesLegacyAdminSettings())->toBeFalse();
});

it('keeps purchase credentials for legacy and affiliate managed affiliates', function (?AffiliateProcessingProfile $profile) {
    $affiliate = new Affiliate();
    $affiliate->setRelation('processingProfile', $profile);

    expect($affiliate->managesOwnPurchaseCredentials())->toBeTrue();
    expect($affiliate->usesLegacyAdminSettings())->toBeTrue();
})->with([
    'missing legacy profile' => null,
    'legacy OresamSub' => fn () => new AffiliateProcessingProfile(['management_mode' => 'affiliate_managed', 'processing_engine' => 'legacy_oresamsub']),
    'affiliate managed multi-parent' => fn () => new AffiliateProcessingProfile(['management_mode' => 'affiliate_managed', 'processing_engine' => 'multi_parent']),
]);
