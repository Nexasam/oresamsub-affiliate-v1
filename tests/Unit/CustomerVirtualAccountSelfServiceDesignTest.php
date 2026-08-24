<?php

it('offers self-service virtual-account generation on the customer accounts page', function () {
    $page = file_get_contents(dirname(__DIR__, 2).'/resources/js/Pages/VirtualAccounts.jsx');

    expect($page)
        ->toContain('user.virtual_accounts.generate')
        ->toContain('Generate virtual account')
        ->toContain('virtualccts.length === 0');

    $middleware = file_get_contents(dirname(__DIR__, 2).'/app/Http/Middleware/HandleInertiaRequests.php');
    expect($middleware)->toContain("'flash' =>");
});
