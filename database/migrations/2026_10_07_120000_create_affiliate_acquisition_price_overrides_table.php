<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_acquisition_price_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_plan_id')->constrained()->cascadeOnDelete();
            $table->decimal('selling_price', 15, 2);
            $table->decimal('max_profit', 15, 2)->nullable();
            $table->timestamps();

            $table->unique(['affiliate_id', 'product_plan_id'], 'affiliate_plan_acquisition_override_unique');
            $table->index(['parent_business_id', 'product_plan_id'], 'affiliate_acquisition_parent_plan_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_acquisition_price_overrides');
    }
};
