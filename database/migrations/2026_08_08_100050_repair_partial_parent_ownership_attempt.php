<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    private string $ownershipMigration = '2026_08_08_100100_add_parent_ownership_and_plan_routing';

    public function up(): void
    {
        if (Schema::hasTable('migrations') && DB::table('migrations')
            ->where('migration', '2026_08_08_100100_add_parent_ownership_and_plan_routing')
            ->exists()) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            $this->normalizeLegacyEngines();
        }

        $columns = collect(['parent_business_id', 'parent_reseller_level_id'])
            ->filter(fn (string $column) => Schema::hasColumn('affiliates', $column))
            ->values();

        if ($columns->isEmpty()) {
            return;
        }

        $hasValues = DB::table('affiliates')->where(function ($query) use ($columns) {
            foreach ($columns as $column) {
                $query->orWhereNotNull($column);
            }
        })->exists();

        if ($hasValues) {
            throw new RuntimeException(
                'Partial parent ownership columns contain data; refusing automatic removal. Review the affiliate ownership values manually.'
            );
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            $this->dropForeignKeyIfPresent('affiliates', 'affiliates_parent_reseller_level_parent_fk');
            $this->dropForeignKeyIfPresent('affiliates', 'affiliates_parent_business_fk');
        }

        foreach (['parent_reseller_level_id', 'parent_business_id'] as $column) {
            if (Schema::hasColumn('affiliates', $column)) {
                Schema::table('affiliates', fn ($table) => $table->dropColumn($column));
            }
        }
    }

    public function down(): void
    {
        // Recovery-only migration. The ownership migration owns the resulting schema.
    }

    private function normalizeLegacyEngines(): void
    {
        foreach (['affiliates', 'product_plans', 'transactions'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $engine = DB::table('information_schema.TABLES')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', $table)
                ->value('ENGINE');

            if (strcasecmp((string) $engine, 'InnoDB') !== 0) {
                DB::statement("ALTER TABLE `{$table}` ENGINE=InnoDB");
            }
        }
    }

    private function dropForeignKeyIfPresent(string $table, string $constraint): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->whereRaw('CONSTRAINT_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }
};
