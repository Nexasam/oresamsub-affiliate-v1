<?php

test('v2 customer desktop sidebar exposes the existing logout action', function () {
    $layout = file_get_contents(dirname(__DIR__, 2).'/resources/js/Layouts/DashboardLayoutV2.jsx');

    expect($layout)
        ->toContain('LogOut')
        ->toContain('router.post("/logout2"')
        ->toContain('<span>Log out</span>');
});
