<?php

use App\Models\AffiliateSettlementLedgerEntry;
use App\Models\AffiliateSettlementWallet;
use Illuminate\Support\Facades\Schema;

it('provides parent scoped affiliate settlement wallet and ledger storage', function () {
    expect(Schema::hasColumns('affiliate_settlement_wallets', [
        'parent_business_id', 'affiliate_id', 'currency', 'available_balance',
        'reserved_balance', 'status',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('affiliate_settlement_ledger_entries', [
            'parent_business_id', 'affiliate_id', 'affiliate_settlement_wallet_id',
            'entry_type', 'amount', 'balance_before', 'balance_after', 'reference',
            'actor_type', 'actor_id', 'reason', 'metadata',
        ]))->toBeTrue()
        ->and((new AffiliateSettlementWallet)->getTable())->toBe('affiliate_settlement_wallets')
        ->and((new AffiliateSettlementLedgerEntry)->getTable())->toBe('affiliate_settlement_ledger_entries');
});
