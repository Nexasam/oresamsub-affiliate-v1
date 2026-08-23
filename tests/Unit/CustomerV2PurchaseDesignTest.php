<?php

test('all customer purchase pages use the shared v2 purchase presentation', function (string $page) {
    $source = file_get_contents(dirname(__DIR__, 2).'/resources/js/Pages/'.$page);

    expect($source)
        ->toContain('V2PurchaseIntro')
        ->toContain('rg-v2-purchase-form')
        ->toContain('customerUi?.version === "v2"');
})->with([
    'data' => 'BuyData.jsx',
    'airtime' => 'BuyAirtime.jsx',
    'cable' => 'BuyCable.jsx',
    'electricity' => 'BuyElectricity.jsx',
]);

test('v2 more contains only implemented customer destinations', function () {
    $page = file_get_contents(dirname(__DIR__, 2).'/resources/js/Pages/More.jsx');
    $source = file_get_contents(dirname(__DIR__, 2).'/resources/js/Components/V2/MorePage.jsx');

    expect($page)->toContain('MorePageV2')
        ->and($source)
        ->toContain('inertia.virtual_accounts.index')
        ->toContain('inertia.transactions.index')
        ->not->toContain('label="Language"')
        ->not->toContain('label="Referrals"')
        ->not->toContain('label="Terms & Policy"');
});
