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
        Schema::create('product_plan_custom_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->index();
            $table->foreignId('product_plan_id')->index();
            $table->foreignId('user_id')->index();
            $table->string('status')->default(1);
            $table->string('price');
            $table->string('added_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_plan_custom_pricings');
    }
};
