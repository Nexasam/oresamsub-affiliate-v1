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
        Schema::create('user_bulk_data_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->index();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('product_plan_category_id')->constrained('product_plan_categories');
            $table->string('bulk_wallet_balance_mb')->default(0);
            $table->string('alltime_bulk_wallet_balance_mb')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_bulk_data_wallets');
    }
};
