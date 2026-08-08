<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_plan_provider_routes', function (Blueprint $table): void {
            $table->unique(
                ['id', 'parent_business_id', 'parent_provider_connection_id'],
                'product_plan_routes_snapshot_identity_unique'
            );
        });

        Schema::table('transactions', function (Blueprint $table): void {
            if (DB::connection()->getDriverName() === 'sqlite') {
                $table->dropForeign(['product_plan_provider_route_id']);
                $table->dropForeign(['parent_provider_connection_id']);
            } else {
                $table->dropForeign('transactions_product_plan_provider_route_fk');
                $table->dropForeign('transactions_parent_provider_connection_fk');
            }

            $table->foreign(
                ['product_plan_provider_route_id', 'parent_business_id', 'parent_provider_connection_id'],
                'transactions_route_parent_connection_fk'
            )->references(['id', 'parent_business_id', 'parent_provider_connection_id'])
                ->on('product_plan_provider_routes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropForeign(
                DB::connection()->getDriverName() === 'sqlite'
                    ? ['product_plan_provider_route_id', 'parent_business_id', 'parent_provider_connection_id']
                    : 'transactions_route_parent_connection_fk'
            );
            $table->foreign('parent_provider_connection_id', 'transactions_parent_provider_connection_fk')
                ->references('id')->on('parent_provider_connections')->restrictOnDelete();
            $table->foreign('product_plan_provider_route_id', 'transactions_product_plan_provider_route_fk')
                ->references('id')->on('product_plan_provider_routes')->restrictOnDelete();
        });

        Schema::table('product_plan_provider_routes', function (Blueprint $table): void {
            $table->dropUnique('product_plan_routes_snapshot_identity_unique');
        });
    }
};
