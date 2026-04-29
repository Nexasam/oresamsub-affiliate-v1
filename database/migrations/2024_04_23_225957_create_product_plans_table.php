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
        Schema::create('product_plans', function (Blueprint $table) {
            $table->id();
            $table->string('api_id')->nullable(); 
            $table->string('product_plan_name');
            $table->foreignId('product_plan_category_id')->constrained('product_plan_categories'); //this carries the productid e.g data and the network e.g mtn if applicable
            $table->string('profit_category')->default('flat')->comment('flat/percent');
            $table->string('admin_cost_price')->nullable();
            $table->string('cost_price')->nullable();
            $table->string('cost_price_1')->nullable();
            $table->string('cost_price_2')->nullable();
            $table->string('cost_price_3')->nullable();
            $table->string('cost_price_4')->nullable();
            $table->string('cost_price_5')->nullable();
            $table->string('cost_price_6')->nullable();
            $table->string('cost_price_7')->nullable();
            $table->string('cost_price_8')->nullable();
            $table->string('cost_price_9')->nullable();
            $table->string('cost_price_10')->nullable();
            $table->string('cost_price_11')->nullable();
            $table->string('cost_price_12')->nullable();
            $table->string('automation_product_plan_id')->nullable();
            $table->string('automation_id')->nullable();
            $table->string('data_size_in_mb')->nullable();
            $table->string('validity_in_days')->nullable();
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
            $table->string('aff_level_1_commission')->default('0');
            $table->string('aff_level_2_commission')->default('0');
            $table->string('aff_level_3_commission')->default('0');
            $table->string('aff_level_4_commission')->default('0');
            $table->string('aff_level_5_commission')->default('0');
            $table->string('aff_level_6_commission')->default('0');
            $table->string('aff_level_7_commission')->default('0');
            $table->string('aff_level_8_commission')->default('0');
            $table->string('aff_level_9_commission')->default('0');
            $table->string('aff_level_10_commission')->default('0');
            $table->string('aff_level_11_commission')->default('0');
            $table->string('aff_level_12_commission')->default('0');
            $table->string('commission_feature')->default('1')->comment('1 on  0 - off');
            $table->string('upline_commission_option')->default('flat')->comment('flat or percent')->nullable();
            $table->string('upline_percentage_commission')->default(0)->comment('if commission_option is percent')->nullable();
            $table->string('upline_flat_commission')->default(0)->comment('if commission_option is percent')->nullable();
            $table->string('upline_commission_cap')->default(1000)->comment('if commission_option is percent')->nullable();
            $table->string('affiliate_visibility')->default(0)->comment(' 0- hidden, 1- visible');
            $table->string('visibility')->default(1)->comment(' 0- hidden, 1- visible');
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
