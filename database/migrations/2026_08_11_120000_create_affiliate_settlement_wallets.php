<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_settlement_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_business_id');
            $table->foreignId('affiliate_id');
            $table->char('currency', 3)->default('NGN');
            $table->decimal('available_balance', 18, 2)->default(0);
            $table->decimal('reserved_balance', 18, 2)->default(0);
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->unique('affiliate_id', 'asw_affiliate_unique');
            $table->foreign('parent_business_id', 'asw_parent_fk')->references('id')->on('parent_businesses')->restrictOnDelete();
            $table->foreign('affiliate_id', 'asw_affiliate_fk')->references('id')->on('affiliates')->restrictOnDelete();
        });

        Schema::create('affiliate_settlement_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_business_id');
            $table->foreignId('affiliate_id');
            $table->foreignId('affiliate_settlement_wallet_id');
            $table->string('entry_type')->index();
            $table->decimal('amount', 18, 2);
            $table->decimal('balance_before', 18, 2);
            $table->decimal('balance_after', 18, 2);
            $table->string('reference');
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id');
            $table->text('reason');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['parent_business_id', 'reference'], 'asle_parent_reference_unique');
            $table->index(['affiliate_id', 'created_at'], 'asle_affiliate_created_idx');
            $table->foreign('parent_business_id', 'asle_parent_fk')->references('id')->on('parent_businesses')->restrictOnDelete();
            $table->foreign('affiliate_id', 'asle_affiliate_fk')->references('id')->on('affiliates')->restrictOnDelete();
            $table->foreign('affiliate_settlement_wallet_id', 'asle_wallet_fk')->references('id')->on('affiliate_settlement_wallets')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_settlement_ledger_entries');
        Schema::dropIfExists('affiliate_settlement_wallets');
    }
};
