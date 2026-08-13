<?php

use App\Models\WalletLog;
use Tests\TestCase;

uses(TestCase::class);

it('converts a wallet funding log into a safe dashboard payload', function () {
    $log = new WalletLog([
        'transaction_id' => 'FUND-1001',
        'transaction_category' => 'SECUREWAVENG_WALLET_FUNDING',
        'balance_before' => '1000.00',
        'balance_after' => '3450.00',
        'description' => 'Wallet credited with 2450.00 after a 50.00 funding charge.',
    ]);
    $log->id = 9;
    $log->created_at = now();
    $log->setRelation('user', new \App\Models\User([
        'first_name' => 'Emeka',
        'last_name' => 'Buyer',
        'email' => 'emeka@example.test',
    ]));

    expect($log->fundingHistoryPayload())->toMatchArray([
        'id' => 9,
        'reference' => 'FUND-1001',
        'provider' => 'Securewaveng',
        'amount' => '2450.00',
        'status' => 'Successful',
        'customer' => 'Emeka Buyer',
        'customer_email' => 'emeka@example.test',
    ]);
});
