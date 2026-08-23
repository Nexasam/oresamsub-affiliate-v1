<?php

test('v2 offsets its header and sidebar while impersonating', function () {
    $layout = file_get_contents(dirname(__DIR__, 2).'/resources/js/Layouts/DashboardLayoutV2.jsx');
    $styles = file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');

    expect($layout)->toContain('has-impersonation')
        ->and($styles)->toContain('.rg-v2-app.has-impersonation .rg-v2-header')
        ->and($styles)->toContain('.rg-v2-app.has-impersonation .rg-v2-sidebar');
});
