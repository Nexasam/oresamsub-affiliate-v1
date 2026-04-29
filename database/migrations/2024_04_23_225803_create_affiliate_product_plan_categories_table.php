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
        Schema::create('affiliate_product_plan_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->index();
            $table->foreignId('plan_category_id')->constrained('product_plan_categories');
            $table->string('product_plan_category_name');
            $table->string('user_level_1_profit')->nullable(); //if not set: 50 is added to cost price automatically
            $table->string('user_level_2_profit')->nullable();
            $table->string('user_level_3_profit')->nullable();
            $table->string('user_level_4_profit')->nullable();
            $table->string('user_level_5_profit')->nullable();
            $table->string('user_level_6_profit')->nullable();
            $table->string('referral_commission_feature')->default(1)->comment('1- on, 0 - off');
            $table->string('referral_commission_method')->default('percent')->comment('flat or percent');
            $table->string('referral_commission_value')->default(5)->comment('if percent, it cannot be more than 100 percent');
            $table->foreignId('product_id')->constrained('products');
            $table->string('is_hot_sales')->default(0)->comment('this is to notify the customer that this product is hotsales');
            $table->string('visibility')->default(1)->nullable();
            $table->string('network_id')->nullable();
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
