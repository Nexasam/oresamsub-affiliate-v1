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
        //WATCH THIS AGAIN
        Schema::create('unique_product_plans', function (Blueprint $table) {
            $table->id();
            $table->string('api_id')->unique();
            $table->string('product_plan_name');
            $table->foreignId('plan_category_id');
            $table->string('data_size_in_mb')->nullable();
            $table->string('validity_in_days')->nullable();
            $table->string('network_id');
            $table->string('product_id'); //starting with data
            $table->string('is_social')->default(0); //0-not 1-it is
            $table->string('cost_price')->nullable(); //this will be by default the most expensive product_plan  + [pricing, which will be done later but say 70 by default]
            $table->string('aff_profit_max_1')->nullable()->comment('the max profit that can be set');
            $table->string('aff_profit_max_2')->nullable()->comment('the max profit that can be set');
            $table->string('aff_profit_max_3')->nullable()->comment('the max profit that can be set');
            $table->string('aff_profit_max_4')->nullable()->comment('the max profit that can be set');
            $table->string('aff_profit_max_5')->nullable()->comment('the max profit that can be set');
            $table->string('aff_profit_max_6')->nullable()->comment('the max profit that can be set');
            $table->string('aff_profit_max_7')->nullable()->comment('the max profit that can be set');
            $table->string('aff_profit_max_8')->nullable()->comment('the max profit that can be set');
            $table->string('aff_profit_max_9')->nullable()->comment('the max profit that can be set');
            $table->string('aff_profit_max_10')->nullable()->comment('the max profit that can be set');
            $table->string('aff_profit_max_11')->nullable()->comment('the max profit that can be set');
            $table->string('aff_profit_max_12')->nullable()->comment('the max profit that can be set');
            $table->string('commission_1')->default(0);
            $table->string('commission_2')->default(0);
            $table->string('commission_3')->default(0);
            $table->string('commission_4')->default(0);
            $table->string('commission_5')->default(0);
            $table->string('commission_6')->default(0);
            $table->string('commission_7')->default(0);
            $table->string('commission_8')->default(0);
            $table->string('commission_9')->default(0);
            $table->string('commission_10')->default(0);
            $table->string('commission_11')->default(0);
            $table->string('commission_12')->default(0);
            $table->string('public_visibility')->default(0)->comment(' 0 - hidden, 1 - visible');
            $table->string('visibility')->default(0)->comment(' 0 - hidden, 1 - visible');
            $table->string('commission_status')->default(0)->comment('0 - hidden, 1 - visible');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unique_product_plans');
    }
};
