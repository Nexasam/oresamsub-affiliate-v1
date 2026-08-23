<?php

test('the classic customer layout exposes the interface switch', function () {
    $layout = file_get_contents(dirname(__DIR__, 2).'/resources/js/Layouts/DashboardLayout.jsx');

    expect($layout)
        ->toContain('UiVersionSwitch')
        ->toContain('<UiVersionSwitch');
});
