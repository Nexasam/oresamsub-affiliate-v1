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
        Schema::create('product_plan_categories', function (Blueprint $table) {
            $table->id();
            $table->string('api_id')->unique();
            $table->string('product_plan_category_name');
            $table->string('referral_commission_feature')->default(1)->comment('1- on, 0 - off');
            $table->string('referral_commission_method')->default('percent')->comment('flat or percent');
            $table->string('referral_commission_value')->default(5)->comment('if percent, it cannot be more than 100 percent');
            $table->foreignId('product_id')->constrained('products');
            $table->string('is_hot_sales')->default(0)->comment('this is to notify the customer that this product is hotsales');
            $table->string('visibility')->default(1)->nullable();
            $table->string('network_id')->nullable();
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
            $table->string('aff_level_11_max_profit')->nullable();
            $table->string('aff_level_12_max_profit')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_plan_categories');
    }
};
