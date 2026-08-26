<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->index(
                ['parent_business_id', 'routing_status', 'created_at', 'affiliate_product_plan_id', 'parent_provider_connection_id'],
                'tx_plan_health_lookup_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex('tx_plan_health_lookup_idx');
        });
    }
};
