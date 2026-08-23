<?php

test('v2 recent transactions open a detail modal instead of navigating', function () {
    $dashboard = file_get_contents(dirname(__DIR__, 2).'/resources/js/Components/V2/CustomerDashboard.jsx');
    $modal = file_get_contents(dirname(__DIR__, 2).'/resources/js/Components/V2/TransactionDetailModal.jsx');

    expect($dashboard)
        ->toContain('selectedTransaction')
        ->toContain('setSelectedTransaction(tx)')
        ->not->toContain('<Link href={route("inertia.transactions.index")} key={tx.id}');
    expect($modal)->toContain('role="dialog"');
});

test('purchase forms provide explicit browser autofill instructions', function (string $page) {
    $source = file_get_contents(dirname(__DIR__, 2).'/resources/js/Pages/'.$page);

    expect($source)
        ->toContain('<form autoComplete="off"')
        ->toContain('data-lpignore="true"')
        ->toContain('data-1p-ignore="true"');
})->with([
    'data' => 'BuyData.jsx',
    'airtime' => 'BuyAirtime.jsx',
    'cable' => 'BuyCable.jsx',
    'electricity' => 'BuyElectricity.jsx',
]);
