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
        Schema::create('affiliate_funding_option_bank_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->index(); //might flow from parent
            $table->string(column: 'short_description')->nullable();
            $table->foreignId('funding_option_id')->constrained('affiliate_funding_options');
            $table->string('bank_code');
            $table->string('visibility_status')->default(0);
            $table->string('rate_category')->default('Flat')->comment('Flat or Percentage');
            $table->string('capped_at')->default('100')->comment('If percentage');
            $table->string('bank_name')->nullable();
            $table->string('bank_charges')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_funding_option_bank_codes');
    }
};
