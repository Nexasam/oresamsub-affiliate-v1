<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_default_profit_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_business_id');
            $table->foreignId('parent_reseller_level_id');
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('calculation_type', 32);
            $table->decimal('value', 14, 2);
            $table->timestamps();
            $table->unique(['parent_business_id', 'parent_reseller_level_id', 'product_id'], 'parent_default_profit_rule_unique');
            $table->foreign('parent_business_id', 'parent_default_profit_rules_parent_fk')
                ->references('id')->on('parent_businesses')->restrictOnDelete();
            $table->foreign(
                ['parent_reseller_level_id', 'parent_business_id'],
                'parent_default_profit_rules_level_parent_fk'
            )->references(['id', 'parent_business_id'])->on('parent_reseller_levels')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_default_profit_rules');
    }
};
