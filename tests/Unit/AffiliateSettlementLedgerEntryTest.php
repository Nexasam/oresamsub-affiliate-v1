<?php

use App\Models\AffiliateSettlementLedgerEntry;
use Tests\TestCase;

uses(TestCase::class);

it('uses an outflow sign for reservations and captures', function (string $type) {
    $entry = new AffiliateSettlementLedgerEntry(['entry_type' => $type]);

    expect($entry->displaySign())->toBe('-')
        ->and($entry->displayColor())->toBe('text-rose-700');
})->with(['purchase_reservation', 'purchase_capture']);

it('uses an inflow sign for releases refunds and credits', function (string $type) {
    $entry = new AffiliateSettlementLedgerEntry(['entry_type' => $type]);

    expect($entry->displaySign())->toBe('+')
        ->and($entry->displayColor())->toBe('text-emerald-700');
})->with(['reservation_release', 'refund', 'manual_credit']);
