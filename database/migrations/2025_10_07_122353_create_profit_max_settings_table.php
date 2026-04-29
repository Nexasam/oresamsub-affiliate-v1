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
        //admin
        Schema::create('profit_max_settings', function (Blueprint $table) {
            $table->id();
            $table->string('aff_level_1_max_profit')->nullable();
            $table->string('aff_level_2_max_profit')->nullable();
            $table->string('aff_level_3_max_profit')->nullable();
            $table->string('aff_level_4_max_profit')->nullable();
            $table->string('aff_level_5_max_profit')->nullable();
            $table->string('aff_level_6_max_profit')->nullable();
            $table->string('aff_level_7_max_profit')->nullable();
            $table->string('aff_level_8_max_profit')->nullable();
            $table->string('aff_level_9_max_profit')->nullable();
            $table->string('aff_level_10_max_profit')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profit_max_settings');
    }
};
