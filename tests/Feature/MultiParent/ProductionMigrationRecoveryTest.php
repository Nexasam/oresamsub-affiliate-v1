<?php

it('runs a guarded partial ownership repair before the failed ownership migration', function () {
    $repair = database_path('migrations/2026_08_08_100050_repair_partial_parent_ownership_attempt.php');
    $ownership = database_path('migrations/2026_08_08_100100_add_parent_ownership_and_plan_routing.php');

    expect(file_exists($repair))->toBeTrue()
        ->and(basename($repair) < basename($ownership))->toBeTrue();

    $source = file_get_contents($repair);
    expect($source)->toContain("where('migration', '2026_08_08_100100_add_parent_ownership_and_plan_routing')")
        ->and($source)->toContain('Partial parent ownership columns contain data')
        ->and($source)->toContain('ENGINE=InnoDB');
});
