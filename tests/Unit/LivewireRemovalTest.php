<?php

test('the application has no runtime dependency on livewire', function () {
    $root = dirname(__DIR__, 2);
    $composer = json_decode(file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $runtimeFiles = [
        $root.'/resources/views/layouts/app.blade.php',
        $root.'/resources/views/admin_dashboard.blade.php',
        $root.'/resources/views/admin_dashboardbackup.blade.php',
        $root.'/resources/views/template2/user/dashboard.blade.php',
        $root.'/resources/views/template2/user/transactions/index.blade.php',
        $root.'/resources/views/template2/user/transactions/detail.blade.php',
        $root.'/resources/views/template2/user/wallet/crystal_pay/index.blade.php',
    ];

    expect($composer['require']['livewire/livewire'] ?? null)->toBeNull()
        ->and($composer['require-dev']['livewire/livewire'] ?? null)->toBeNull()
        ->and(glob($root.'/app/Livewire/*.php') ?: [])->toBeEmpty()
        ->and(glob($root.'/resources/views/livewire/*.blade.php') ?: [])->toBeEmpty();

    foreach ($runtimeFiles as $file) {
        $source = file_get_contents($file);

        expect($source)
            ->not->toContain('@livewire')
            ->not->toContain('<livewire:')
            ->not->toContain('Livewire.');
    }
});
