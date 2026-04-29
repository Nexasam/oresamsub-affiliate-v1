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
        Schema::create('affiliate_user_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->index();
            $table->string('user_plan_name');
            $table->string('updated_user_plan_name')->nullable();
            $table->string('plan_level')->comment('user plan level');
            $table->string('is_default')->nullable();
            $table->string('visibility')->default(1);
            $table->string('max_profit')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_user_plans');
    }
};
