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
        //for the users of affiliates
        Schema::create('user_plans', function (Blueprint $table) {
            $table->id();
            $table->string('api_id')->unique();
            $table->string('user_plan_name')->unique();
            $table->string('updated_user_plan_name')->unique()->nullable();
            $table->string('plan_level')->comment('user plan level');
            $table->string('is_default')->nullable();
            $table->string('visibility')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_plans');
    }
};
