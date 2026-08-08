<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->unique(['id', 'parent_business_id'], 'affiliates_id_parent_unique');
        });

        Schema::create('affiliate_service_profit_caps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_business_id');
            $table->foreignId('affiliate_id');
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('customer_level');
            $table->string('calculation_type', 16);
            $table->decimal('max_value', 14, 2);
            $table->timestamps();
            $table->unique(['affiliate_id', 'product_id', 'customer_level'], 'affiliate_service_profit_cap_unique');
            $table->foreign('parent_business_id', 'affiliate_profit_caps_parent_fk')->references('id')->on('parent_businesses')->restrictOnDelete();
            $table->foreign(['affiliate_id', 'parent_business_id'], 'affiliate_profit_caps_affiliate_parent_fk')
                ->references(['id', 'parent_business_id'])->on('affiliates')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_service_profit_caps');
        Schema::table('affiliates', fn (Blueprint $table) => $table->dropUnique('affiliates_id_parent_unique'));
    }
};
