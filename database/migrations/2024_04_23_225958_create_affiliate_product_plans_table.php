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
        Schema::create('affiliate_product_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->index();
            $table->string('product_plan_name'); //the name the affiliate might decide to call the plan
            $table->foreignId('product_plan_id')->constrained('product_plans'); 
            $table->string('data_size_in_mb')->nullable();
            $table->string('validity_in_days')->nullable();
            $table->string('user_level_1_profit')->nullable(); //if not set: 50 is added to cost price automatically
            $table->string('user_level_2_profit')->nullable();
            $table->string('user_level_3_profit')->nullable();
            $table->string('user_level_4_profit')->nullable();
            $table->string('user_level_5_profit')->nullable();
            $table->string('user_level_6_profit')->nullable();
            $table->string('user_level_1_commission')->default('0');
            $table->string('user_level_2_commission')->default('0');
            $table->string('user_level_3_commission')->default('0');
            $table->string('user_level_4_commission')->default('0');
            $table->string('user_level_5_commission')->default('0');
            $table->string('user_level_6_commission')->default('0');
            $table->string('commission_feature')->default('1')->comment('1 on  0 - off');
            $table->string('upline_commission_option')->default('flat')->comment('flat or percent')->nullable();
            $table->string('upline_percentage_commission')->default(0)->comment('if commission_option is percent')->nullable();
            $table->string('upline_flat_commission')->default(0)->comment('if commission_option is percent')->nullable();
            $table->string('upline_commission_cap')->default(1000)->comment('if commission_option is percent')->nullable();
            $table->string('visibility_from_admin')->default(1)->comment(' 0- hidden, 1- visible'); 
            $table->string('visibility')->default(1)->comment(' 0- hidden, 1- visible, affiliate can decide to turn off'); 
            $table->string('public_visibility')->default(1)->comment(' 0- hidden, 1- visible');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_plans');
    }
};
