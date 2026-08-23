<?php

test('the pwa installer is affiliate branded and supports manual installation requests', function () {
    $popup = file_get_contents(dirname(__DIR__, 2).'/resources/js/Components/PwaInstallPopup.jsx');

    expect($popup)
        ->toContain('appName')
        ->toContain('pwa:request-install')
        ->toContain('Add to Home Screen')
        ->not->toContain('Install OresamSub');
});

test('v2 exposes install actions in its sidebar and more page', function () {
    $layout = file_get_contents(dirname(__DIR__, 2).'/resources/js/Layouts/DashboardLayoutV2.jsx');
    $more = file_get_contents(dirname(__DIR__, 2).'/resources/js/Pages/More.jsx');

    expect($layout)->toContain('<InstallAppButton')
        ->and($more)->toContain('<InstallAppButton');
});
