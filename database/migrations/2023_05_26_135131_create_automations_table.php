<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->string('automation_name');
            $table->string('bank_name')->nullable();
            $table->string('bank_accounts')->nullable();
            $table->string('electricity_url')->nullable();
            $table->string('cable_url')->nullable();
            $table->string('airtime_url')->nullable();
            $table->string('data_url')->nullable();
            $table->string('automation_group')->nullable();
            $table->string('whatsapp_support_link')->nullable();
            $table->string('slug')->unique();
            $table->string('api_secret_key')->default(NULL)->nullable();
            $table->string('api_public_key')->default(NULL)->nullable();
            $table->string('api_password')->default(NULL)->nullable();
            $table->string('domain_url')->default(NULL)->nullable();
            $table->string('activation_status')->default('1')->comment('1- Activated / 0 - Deactivated');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automations');
    }
};
