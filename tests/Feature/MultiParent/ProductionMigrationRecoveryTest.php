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

it('makes the ownership migration resumable after non-transactional mysql ddl failures', function () {
    $source = file_get_contents(database_path('migrations/2026_08_08_100100_add_parent_ownership_and_plan_routing.php'));

    expect($source)->toContain("ensureColumn('affiliates', 'parent_business_id'")
        ->and($source)->toContain("Schema::hasTable('product_plan_parent_prices')")
        ->and($source)->toContain('constraintExists')
        ->and($source)->toContain('indexExists')
        ->and($source)->toContain('triggerExists');
});

it('normalizes mysql parent foundation tables to innodb before adding foreign keys', function () {
    $source = file_get_contents(database_path('migrations/2026_08_08_100100_add_parent_ownership_and_plan_routing.php'));

    expect($source)->toContain('$this->normalizeReferencedTableEngines();')
        ->and($source)->toContain("'parent_businesses'")
        ->and($source)->toContain("'parent_reseller_levels'")
        ->and($source)->toContain("'parent_provider_connections'")
        ->and($source)->toContain('ENGINE=InnoDB');
});

it('makes the provider approval migration resumable and normalizes its foreign tables', function () {
    $source = file_get_contents(database_path('migrations/2026_08_10_160000_add_approval_to_parent_provider_connections.php'));

    expect($source)->toContain("ensureColumn('parent_provider_connections', 'approval_status'")
        ->and($source)->toContain("normalizeTableEngine('admins')")
        ->and($source)->toContain("normalizeTableEngine('parent_provider_connections')")
        ->and($source)->toContain('constraintExists')
        ->and($source)->toContain('ENGINE=InnoDB');
});

it('uses innodb by default and makes the multi parent funding migration resumable', function () {
    $config = file_get_contents(config_path('database.php'));
    $source = file_get_contents(database_path('migrations/2026_08_11_100000_create_multi_parent_funding_tables.php'));

    expect($config)->toContain("'engine' => 'InnoDB'")
        ->and($source)->toContain("Schema::hasTable('funding_providers')")
        ->and($source)->toContain("'funding_webhook_events', 'user_virtual_accounts'")
        ->and($source)->toContain("ensureColumn('user_virtual_accounts', 'parent_funding_provider_id'")
        ->and($source)->toContain('ensureForeign')
        ->and($source)->toContain("\$table->engine = 'InnoDB'");
});
