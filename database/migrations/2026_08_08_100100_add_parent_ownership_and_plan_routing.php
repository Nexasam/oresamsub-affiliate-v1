<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeReferencedTableEngines();

        $this->ensureColumn('affiliates', 'parent_business_id', fn (Blueprint $table) => $table->foreignId('parent_business_id')->nullable());
        $this->ensureColumn('affiliates', 'parent_reseller_level_id', fn (Blueprint $table) => $table->foreignId('parent_reseller_level_id')->nullable());
        $this->ensureIndex('affiliates', ['parent_business_id'], 'affiliates_parent_business_id_index');
        $this->ensureIndex('affiliates', ['parent_reseller_level_id'], 'affiliates_parent_reseller_level_id_index');
        $this->ensureForeign('affiliates', 'affiliates_parent_business_fk', ['parent_business_id'], 'parent_businesses', ['id']);
        $this->ensureForeign('affiliates', 'affiliates_parent_reseller_level_parent_fk', ['parent_reseller_level_id', 'parent_business_id'], 'parent_reseller_levels', ['id', 'parent_business_id']);

        $this->ensureColumn('product_plans', 'parent_business_id', fn (Blueprint $table) => $table->foreignId('parent_business_id')->nullable());
        $this->ensureIndex('product_plans', ['parent_business_id'], 'product_plans_parent_business_id_index');
        $this->ensureForeign('product_plans', 'product_plans_parent_business_fk', ['parent_business_id'], 'parent_businesses', ['id']);
        $this->ensureUnique('product_plans', ['id', 'parent_business_id'], 'product_plans_id_parent_unique');

        if (! Schema::hasTable('product_plan_parent_prices')) Schema::create('product_plan_parent_prices', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('parent_business_id');
            $table->foreignId('product_plan_id');
            $table->foreignId('parent_reseller_level_id');
            $table->decimal('selling_price', 14, 2);
            $table->decimal('max_profit', 14, 2)->nullable();
            $table->timestamps();
            $table->unique(
                ['product_plan_id', 'parent_reseller_level_id'],
                'product_plan_parent_prices_plan_level_unique'
            );
            $table->foreign('parent_business_id', 'plan_parent_prices_parent_business_fk')
                ->references('id')->on('parent_businesses')->restrictOnDelete();
            $table->foreign(
                ['product_plan_id', 'parent_business_id'],
                'plan_parent_prices_plan_parent_fk'
            )->references(['id', 'parent_business_id'])->on('product_plans')->restrictOnDelete();
            $table->foreign(
                ['parent_reseller_level_id', 'parent_business_id'],
                'plan_parent_prices_level_parent_fk'
            )->references(['id', 'parent_business_id'])->on('parent_reseller_levels')->restrictOnDelete();
        });

        if (! Schema::hasTable('product_plan_provider_routes')) Schema::create('product_plan_provider_routes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('parent_business_id');
            $table->foreignId('product_plan_id');
            $table->foreignId('parent_provider_connection_id');
            $table->string('provider_plan_id');
            $table->unsignedInteger('priority');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['product_plan_id', 'priority'], 'product_plan_routes_plan_priority_unique');
            $table->foreign('parent_business_id', 'product_plan_routes_parent_business_fk')
                ->references('id')->on('parent_businesses')->restrictOnDelete();
            $table->foreign(
                ['product_plan_id', 'parent_business_id'],
                'product_plan_routes_plan_parent_fk'
            )->references(['id', 'parent_business_id'])->on('product_plans')->restrictOnDelete();
            $table->foreign(
                ['parent_provider_connection_id', 'parent_business_id'],
                'product_plan_routes_connection_parent_fk'
            )->references(['id', 'parent_business_id'])->on('parent_provider_connections')->restrictOnDelete();
        });

        if (! Schema::hasTable('multi_parent_migration_audits')) Schema::create('multi_parent_migration_audits', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->uuid('batch_uuid')->index();
            $table->string('action')->index();
            $table->string('entity_type')->index();
            $table->unsignedBigInteger('entity_id')->index();
            $table->text('from_value')->nullable();
            $table->text('to_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        $transactionColumns = [
            'parent_business_id' => fn (Blueprint $t) => $t->foreignId('parent_business_id')->nullable(),
            'parent_provider_connection_id' => fn (Blueprint $t) => $t->foreignId('parent_provider_connection_id')->nullable(),
            'product_plan_provider_route_id' => fn (Blueprint $t) => $t->foreignId('product_plan_provider_route_id')->nullable(),
            'provider_plan_id_snapshot' => fn (Blueprint $t) => $t->string('provider_plan_id_snapshot')->nullable(),
            'provider_reference' => fn (Blueprint $t) => $t->string('provider_reference')->nullable(),
            'routing_status' => fn (Blueprint $t) => $t->string('routing_status')->nullable(),
            'provider_cost_snapshot' => fn (Blueprint $t) => $t->decimal('provider_cost_snapshot', 14, 2)->nullable(),
            'parent_cost_snapshot' => fn (Blueprint $t) => $t->decimal('parent_cost_snapshot', 14, 2)->nullable(),
            'affiliate_cost_snapshot' => fn (Blueprint $t) => $t->decimal('affiliate_cost_snapshot', 14, 2)->nullable(),
            'customer_price_snapshot' => fn (Blueprint $t) => $t->decimal('customer_price_snapshot', 14, 2)->nullable(),
            'parent_profit_snapshot' => fn (Blueprint $t) => $t->decimal('parent_profit_snapshot', 14, 2)->nullable(),
            'affiliate_profit_snapshot' => fn (Blueprint $t) => $t->decimal('affiliate_profit_snapshot', 14, 2)->nullable(),
        ];
        foreach ($transactionColumns as $column => $definition) $this->ensureColumn('transactions', $column, $definition);
        foreach (['parent_business_id', 'parent_provider_connection_id', 'product_plan_provider_route_id', 'provider_reference', 'routing_status'] as $column) {
            $this->ensureIndex('transactions', [$column], "transactions_{$column}_index");
        }
        $this->ensureForeign('transactions', 'transactions_parent_business_fk', ['parent_business_id'], 'parent_businesses', ['id']);
        $this->ensureForeign('transactions', 'transactions_parent_provider_connection_fk', ['parent_provider_connection_id'], 'parent_provider_connections', ['id']);
        $this->ensureForeign('transactions', 'transactions_product_plan_provider_route_fk', ['product_plan_provider_route_id'], 'product_plan_provider_routes', ['id']);

        $this->addAffiliateOwnershipTriggers();
    }

    private function normalizeReferencedTableEngines(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $tables = [
            'parent_businesses',
            'parent_admins',
            'provider_connections',
            'parent_provider_connections',
            'parent_reseller_levels',
            'affiliates',
            'product_plans',
            'transactions',
            'product_plan_parent_prices',
            'product_plan_provider_routes',
            'multi_parent_migration_audits',
        ];

        foreach ($tables as $table) {
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

    private function ensureColumn(string $table, string $column, callable $definition): void
    {
        if (! Schema::hasColumn($table, $column)) Schema::table($table, $definition);
    }

    private function ensureIndex(string $table, array $columns, string $name): void
    {
        if (! $this->indexExists($table, $name)) Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
    }

    private function ensureUnique(string $table, array $columns, string $name): void
    {
        if (! $this->indexExists($table, $name)) Schema::table($table, fn (Blueprint $t) => $t->unique($columns, $name));
    }

    private function ensureForeign(string $table, string $name, array $columns, string $foreignTable, array $foreignColumns): void
    {
        if (! $this->constraintExists($table, $name)) Schema::table($table, fn (Blueprint $t) => $t->foreign($columns, $name)->references($foreignColumns)->on($foreignTable)->restrictOnDelete());
    }

    private function indexExists(string $table, string $name): bool
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA index_list(`{$table}`)"))->contains(fn ($row) => $row->name === $name);
        }
        return DB::table('information_schema.STATISTICS')->whereRaw('TABLE_SCHEMA = DATABASE()')->where('TABLE_NAME', $table)->where('INDEX_NAME', $name)->exists();
    }

    private function constraintExists(string $table, string $name): bool
    {
        if (DB::connection()->getDriverName() === 'sqlite') return false;
        return DB::table('information_schema.TABLE_CONSTRAINTS')->whereRaw('CONSTRAINT_SCHEMA = DATABASE()')->where('TABLE_NAME', $table)->where('CONSTRAINT_NAME', $name)->exists();
    }

    private function triggerExists(string $name): bool
    {
        if (DB::connection()->getDriverName() === 'sqlite') return false;
        return DB::table('information_schema.TRIGGERS')->whereRaw('TRIGGER_SCHEMA = DATABASE()')->where('TRIGGER_NAME', $name)->exists();
    }

    public function down(): void
    {
        $this->dropAffiliateOwnershipTriggers();

        Schema::table('transactions', function (Blueprint $table) {
            if (DB::connection()->getDriverName() === 'sqlite') {
                $table->dropForeign(['product_plan_provider_route_id']);
                $table->dropForeign(['parent_provider_connection_id']);
                $table->dropForeign(['parent_business_id']);
            } else {
                $table->dropForeign('transactions_product_plan_provider_route_fk');
                $table->dropForeign('transactions_parent_provider_connection_fk');
                $table->dropForeign('transactions_parent_business_fk');
            }
            $table->dropIndex(['parent_business_id']);
            $table->dropIndex(['parent_provider_connection_id']);
            $table->dropIndex(['product_plan_provider_route_id']);
            $table->dropIndex(['provider_reference']);
            $table->dropIndex(['routing_status']);
            $table->dropColumn([
                'parent_business_id', 'parent_provider_connection_id', 'product_plan_provider_route_id',
                'provider_plan_id_snapshot', 'provider_reference', 'routing_status',
                'provider_cost_snapshot', 'parent_cost_snapshot', 'affiliate_cost_snapshot',
                'customer_price_snapshot', 'parent_profit_snapshot', 'affiliate_profit_snapshot',
            ]);
        });

        Schema::dropIfExists('multi_parent_migration_audits');
        Schema::dropIfExists('product_plan_provider_routes');
        Schema::dropIfExists('product_plan_parent_prices');

        Schema::table('product_plans', function (Blueprint $table) {
            $table->dropForeign(
                DB::connection()->getDriverName() === 'sqlite'
                    ? ['parent_business_id']
                    : 'product_plans_parent_business_fk'
            );
            $table->dropUnique('product_plans_id_parent_unique');
            $table->dropIndex(['parent_business_id']);
            $table->dropColumn('parent_business_id');
        });

        Schema::table('affiliates', function (Blueprint $table) {
            if (DB::connection()->getDriverName() === 'sqlite') {
                $table->dropForeign(['parent_reseller_level_id', 'parent_business_id']);
                $table->dropForeign(['parent_business_id']);
            } else {
                $table->dropForeign('affiliates_parent_reseller_level_parent_fk');
                $table->dropForeign('affiliates_parent_business_fk');
            }
            $table->dropIndex(['parent_reseller_level_id']);
            $table->dropIndex(['parent_business_id']);
            $table->dropColumn(['parent_reseller_level_id', 'parent_business_id']);
        });
    }

    private function addAffiliateOwnershipTriggers(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            if (! $this->triggerExists('affiliates_parent_ownership_insert')) DB::unprepared(<<<'SQL'
                CREATE TRIGGER affiliates_parent_ownership_insert
                BEFORE INSERT ON affiliates
                FOR EACH ROW
                BEGIN
                    IF (NEW.parent_business_id IS NULL) <> (NEW.parent_reseller_level_id IS NULL) THEN
                        SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Affiliate parent and reseller level must both be null or both be set.';
                    END IF;
                END
                SQL);

            if (! $this->triggerExists('affiliates_parent_ownership_update')) DB::unprepared(<<<'SQL'
                CREATE TRIGGER affiliates_parent_ownership_update
                BEFORE UPDATE ON affiliates
                FOR EACH ROW
                BEGIN
                    IF (NEW.parent_business_id IS NULL) <> (NEW.parent_reseller_level_id IS NULL) THEN
                        SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Affiliate parent and reseller level must both be null or both be set.';
                    END IF;
                END
                SQL);

            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS affiliates_parent_ownership_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS affiliates_parent_ownership_update');
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER affiliates_parent_ownership_insert
            BEFORE INSERT ON affiliates
            FOR EACH ROW
            WHEN (NEW.parent_business_id IS NULL) <> (NEW.parent_reseller_level_id IS NULL)
            BEGIN
                SELECT RAISE(ABORT, 'Affiliate parent and reseller level must both be null or both be set.');
            END
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER affiliates_parent_ownership_update
            BEFORE UPDATE OF parent_reseller_level_id, parent_business_id ON affiliates
            FOR EACH ROW
            WHEN (NEW.parent_business_id IS NULL) <> (NEW.parent_reseller_level_id IS NULL)
            BEGIN
                SELECT RAISE(ABORT, 'Affiliate parent and reseller level must both be null or both be set.');
            END
            SQL);
    }

    private function dropAffiliateOwnershipTriggers(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS affiliates_parent_ownership_update');
        DB::unprepared('DROP TRIGGER IF EXISTS affiliates_parent_ownership_insert');
    }
};
