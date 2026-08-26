<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_connections', function (Blueprint $table): void {
            $table->dropUnique('provider_connections_adapter_unique');
            $table->index('adapter', 'provider_connections_adapter_index');
        });

        Schema::create('provider_configuration_promotions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_provider_connection_id')->constrained('parent_provider_connections', 'id', 'pcp_promotions_parent_fk')->restrictOnDelete();
            $table->foreignId('source_provider_connection_id')->nullable()->constrained('provider_connections', 'id', 'pcp_promotions_source_fk')->nullOnDelete();
            $table->foreignId('target_provider_connection_id')->constrained('provider_connections', 'id', 'pcp_promotions_target_fk')->restrictOnDelete();
            $table->foreignId('provider_adapter_id')->nullable()->constrained('provider_adapters', 'id', 'pcp_promotions_adapter_fk')->nullOnDelete();
            $table->foreignId('promoted_by_admin_id')->constrained('admins', 'id', 'pcp_promotions_admin_fk')->restrictOnDelete();
            $table->string('strategy');
            $table->json('source_snapshot');
            $table->json('target_before_snapshot')->nullable();
            $table->json('target_after_snapshot');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_configuration_promotions');
        Schema::table('provider_connections', function (Blueprint $table): void {
            $table->dropIndex('provider_connections_adapter_index');
            $table->unique('adapter', 'provider_connections_adapter_unique');
        });
    }
};
