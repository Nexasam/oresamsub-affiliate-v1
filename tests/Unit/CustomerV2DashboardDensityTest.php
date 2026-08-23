<?php

test('the v2 dashboard keeps the wallet hero compact on mobile', function () {
    $source = file_get_contents(dirname(__DIR__, 2).'/resources/js/Components/V2/CustomerDashboard.jsx');

    expect($source)
        ->toContain('p-4 text-white')
        ->toContain('text-2xl font-black')
        ->toContain('min-h-[112px]')
        ->not->toContain('p-6 text-white');
});
