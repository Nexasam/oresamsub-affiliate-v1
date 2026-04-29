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
        Schema::create('commissions', function (Blueprint $table) {    
            $table->id();
            $table->foreignId('affiliate_id')->index();
            $table->foreignId('transaction_id');
            $table->string('commission')->default(0);
            $table->foreignId('beneficiary');
            $table->foreignId('transaction_by');
            $table->string('payout_status')->default(0)->comment('it means status of conversion into the customer wallet');
            $table->string('payout_by_who')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
