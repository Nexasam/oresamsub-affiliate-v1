<?php

use App\Models\Affiliate;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Services\Wallet\AffiliateSettlementWalletService;
use Illuminate\Validation\ValidationException;

function settlementFundingContext(string $suffix): array
{
    $parent = ParentBusiness::create(['name' => "Parent {$suffix}", 'slug' => "parent-{$suffix}"]);
    $admin = ParentAdmin::create(['parent_business_id' => $parent->id, 'name' => 'Owner', 'email' => "owner-{$suffix}@example.test", 'password' => 'password', 'active' => true]);
    $level = $parent->resellerLevels()->create(['name' => 'Basic', 'position' => 1, 'status' => 'active']);
    $affiliate = Affiliate::create([
        'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id,
        'name' => "Affiliate {$suffix}", 'slug' => "affiliate-{$suffix}", 'affiliate_plan_id' => 1,
        'ip_address' => "managed-{$suffix}", 'contact_phone' => "080{$suffix}",
        'contact_email' => "affiliate-{$suffix}@example.test", 'parent_key' => "key-{$suffix}",
        'parent_email' => "parent-{$suffix}@example.test",
    ]);

    return compact('parent', 'admin', 'affiliate');
}

it('credits an affiliate settlement wallet and writes an immutable audit entry', function () {
    ['parent' => $parent, 'admin' => $admin, 'affiliate' => $affiliate] = settlementFundingContext('1001');

    $wallet = app(AffiliateSettlementWalletService::class)->credit(
        $affiliate, $admin, '1250.75', 'BANK-TRANSFER-1001', 'Verified bank transfer',
    );

    expect($wallet->available_balance)->toBe('1250.75')
        ->and($wallet->reserved_balance)->toBe('0.00')
        ->and($affiliate->fresh()->settlementWallet->id)->toBe($wallet->id);

    $this->assertDatabaseHas('affiliate_settlement_ledger_entries', [
        'parent_business_id' => $parent->id,
        'affiliate_id' => $affiliate->id,
        'entry_type' => 'manual_credit',
        'amount' => '1250.75',
        'balance_before' => '0.00',
        'balance_after' => '1250.75',
        'reference' => 'BANK-TRANSFER-1001',
        'actor_type' => 'parent_admin',
        'actor_id' => $admin->id,
    ]);
});

it('rejects duplicate references without crediting twice', function () {
    ['admin' => $admin, 'affiliate' => $affiliate] = settlementFundingContext('1002');
    $service = app(AffiliateSettlementWalletService::class);
    $service->credit($affiliate, $admin, '500', 'DUPLICATE-1002', 'First verified payment');

    expect(fn () => $service->credit($affiliate, $admin, '500', 'DUPLICATE-1002', 'Repeated payment'))
        ->toThrow(ValidationException::class);

    expect($affiliate->fresh()->settlementWallet->available_balance)->toBe('500.00')
        ->and($affiliate->settlementWallet->ledgerEntries()->count())->toBe(1);
});

it('rejects a parent administrator attempting to fund another parents affiliate', function () {
    ['admin' => $admin] = settlementFundingContext('1003');
    ['affiliate' => $otherAffiliate] = settlementFundingContext('1004');

    expect(fn () => app(AffiliateSettlementWalletService::class)->credit(
        $otherAffiliate, $admin, '100', 'CROSS-PARENT-1003', 'Invalid cross-parent credit',
    ))->toThrow(ValidationException::class);

    expect($otherAffiliate->fresh()->settlementWallet)->toBeNull();
});

it('reserves captures releases and refunds settlement funds exactly once', function () {
    ['admin' => $admin, 'affiliate' => $affiliate] = settlementFundingContext('1005');
    $service = app(AffiliateSettlementWalletService::class);
    $service->credit($affiliate, $admin, '1000', 'FUND-1005', 'Initial verified settlement funding');

    $wallet = $service->reserve($affiliate, '250.00', 'ORDER-1005', 'purchase', 91);
    expect($wallet->available_balance)->toBe('750.00')->and($wallet->reserved_balance)->toBe('250.00');
    $service->reserve($affiliate, '250.00', 'ORDER-1005', 'purchase', 91);
    expect($affiliate->fresh()->settlementWallet->ledgerEntries()->where('entry_type', 'purchase_reservation')->count())->toBe(1);

    $wallet = $service->capture($affiliate, '250.00', 'ORDER-1005', 'provider', 91);
    expect($wallet->available_balance)->toBe('750.00')->and($wallet->reserved_balance)->toBe('0.00');
    $service->capture($affiliate, '250.00', 'ORDER-1005', 'provider', 91);
    expect($affiliate->fresh()->settlementWallet->ledgerEntries()->where('entry_type', 'purchase_capture')->count())->toBe(1);

    $wallet = $service->refund($affiliate, '250.00', 'ORDER-1005', 'reconciliation', 91);
    expect($wallet->available_balance)->toBe('1000.00');
    $service->refund($affiliate, '250.00', 'ORDER-1005', 'reconciliation', 91);
    expect($affiliate->fresh()->settlementWallet->ledgerEntries()->where('entry_type', 'refund')->count())->toBe(1);

    $service->reserve($affiliate, '300.00', 'ORDER-1006', 'purchase', 92);
    $wallet = $service->release($affiliate, '300.00', 'ORDER-1006', 'provider_failure', 92);
    expect($wallet->available_balance)->toBe('1000.00')->and($wallet->reserved_balance)->toBe('0.00');
});

it('rejects reservations above the available settlement balance', function () {
    ['admin' => $admin, 'affiliate' => $affiliate] = settlementFundingContext('1006');
    $service = app(AffiliateSettlementWalletService::class);
    $service->credit($affiliate, $admin, '100', 'FUND-1006', 'Initial verified settlement funding');

    expect(fn () => $service->reserve($affiliate, '100.01', 'ORDER-INSUFFICIENT', 'purchase', 1))
        ->toThrow(ValidationException::class);

    expect($affiliate->fresh()->settlementWallet->available_balance)->toBe('100.00')
        ->and($affiliate->settlementWallet->reserved_balance)->toBe('0.00');
});
