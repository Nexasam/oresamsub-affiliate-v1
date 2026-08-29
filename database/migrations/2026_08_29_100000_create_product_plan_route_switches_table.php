<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_plan_route_switches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_business_id')->index();
            $table->unsignedBigInteger('product_plan_id')->index();
            $table->unsignedBigInteger('from_parent_provider_connection_id')->nullable()->index('plan_route_switches_from_idx');
            $table->unsignedBigInteger('to_parent_provider_connection_id')->index('plan_route_switches_to_idx');
            $table->unsignedBigInteger('parent_admin_id')->nullable()->index();
            $table->string('provider_plan_id');
            $table->string('source')->default('parent_admin');
            $table->timestamps();

            $table->index(['parent_business_id', 'product_plan_id', 'created_at'], 'plan_route_switches_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_plan_route_switches');
    }
};
