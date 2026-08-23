<?php

test('dashboard and transaction history share one v2 receipt modal', function () {
    $dashboard = file_get_contents(dirname(__DIR__, 2).'/resources/js/Components/V2/CustomerDashboard.jsx');
    $transactions = file_get_contents(dirname(__DIR__, 2).'/resources/js/Pages/Transactions.jsx');

    expect($dashboard)->toContain('TransactionDetailModal')
        ->and($transactions)->toContain('TransactionDetailModal')
        ->and($transactions)->toContain('customerUi?.version === "v2"');
});
