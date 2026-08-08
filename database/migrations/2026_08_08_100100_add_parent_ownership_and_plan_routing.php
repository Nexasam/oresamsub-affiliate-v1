<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->foreignId('parent_business_id')->nullable()->index();
            $table->foreignId('parent_reseller_level_id')->nullable()->index();
            $table->foreign('parent_business_id', 'affiliates_parent_business_fk')
                ->references('id')->on('parent_businesses')->restrictOnDelete();
            $table->foreign(
                ['parent_reseller_level_id', 'parent_business_id'],
                'affiliates_parent_reseller_level_parent_fk'
            )->references(['id', 'parent_business_id'])->on('parent_reseller_levels')->restrictOnDelete();
        });

        Schema::table('product_plans', function (Blueprint $table) {
            $table->foreignId('parent_business_id')->nullable()->index();
            $table->foreign('parent_business_id', 'product_plans_parent_business_fk')
                ->references('id')->on('parent_businesses')->restrictOnDelete();
            $table->unique(['id', 'parent_business_id'], 'product_plans_id_parent_unique');
        });

        Schema::create('product_plan_parent_prices', function (Blueprint $table) {
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

        Schema::create('product_plan_provider_routes', function (Blueprint $table) {
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

        Schema::create('multi_parent_migration_audits', function (Blueprint $table) {
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

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('parent_business_id')->nullable()->index();
            $table->foreignId('parent_provider_connection_id')->nullable()->index();
            $table->foreignId('product_plan_provider_route_id')->nullable()->index();
            $table->string('provider_plan_id_snapshot')->nullable();
            $table->string('provider_reference')->nullable()->index();
            $table->string('routing_status')->nullable()->index();
            $table->decimal('provider_cost_snapshot', 14, 2)->nullable();
            $table->decimal('parent_cost_snapshot', 14, 2)->nullable();
            $table->decimal('affiliate_cost_snapshot', 14, 2)->nullable();
            $table->decimal('customer_price_snapshot', 14, 2)->nullable();
            $table->decimal('parent_profit_snapshot', 14, 2)->nullable();
            $table->decimal('affiliate_profit_snapshot', 14, 2)->nullable();
            $table->foreign('parent_business_id', 'transactions_parent_business_fk')
                ->references('id')->on('parent_businesses')->restrictOnDelete();
            $table->foreign('parent_provider_connection_id', 'transactions_parent_provider_connection_fk')
                ->references('id')->on('parent_provider_connections')->restrictOnDelete();
            $table->foreign('product_plan_provider_route_id', 'transactions_product_plan_provider_route_fk')
                ->references('id')->on('product_plan_provider_routes')->restrictOnDelete();
        });

        $this->addSqliteAffiliateParentTrigger();
    }

    public function down(): void
    {
        $this->dropSqliteAffiliateParentTrigger();

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

    private function addSqliteAffiliateParentTrigger(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER affiliates_parent_reseller_level_parent_insert
            BEFORE INSERT ON affiliates
            FOR EACH ROW
            WHEN NEW.parent_reseller_level_id IS NOT NULL
              AND NOT EXISTS (
                SELECT 1 FROM parent_reseller_levels
                WHERE id = NEW.parent_reseller_level_id
                  AND parent_business_id = NEW.parent_business_id
              )
            BEGIN
                SELECT RAISE(ABORT, 'Affiliate reseller level must belong to its parent.');
            END
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER affiliates_parent_reseller_level_parent_update
            BEFORE UPDATE OF parent_reseller_level_id, parent_business_id ON affiliates
            FOR EACH ROW
            WHEN NEW.parent_reseller_level_id IS NOT NULL
              AND NOT EXISTS (
                SELECT 1 FROM parent_reseller_levels
                WHERE id = NEW.parent_reseller_level_id
                  AND parent_business_id = NEW.parent_business_id
              )
            BEGIN
                SELECT RAISE(ABORT, 'Affiliate reseller level must belong to its parent.');
            END
            SQL);
    }

    private function dropSqliteAffiliateParentTrigger(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS affiliates_parent_reseller_level_parent_update');
        DB::unprepared('DROP TRIGGER IF EXISTS affiliates_parent_reseller_level_parent_insert');
    }
};
