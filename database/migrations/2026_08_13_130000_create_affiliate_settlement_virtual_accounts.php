<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('affiliate_settlement_virtual_accounts')) return;

        Schema::create('affiliate_settlement_virtual_accounts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('parent_business_id')->constrained(indexName: 'settlement_va_parent_fk')->restrictOnDelete();
            $table->foreignId('affiliate_id')->constrained(indexName: 'settlement_va_affiliate_fk')->restrictOnDelete();
            $table->foreignId('parent_funding_provider_id')->constrained(indexName: 'settlement_va_provider_fk')->restrictOnDelete();
            $table->string('wallet_purpose')->default('settlement')->index();
            $table->string('bank_name')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('account_name');
            $table->string('account_number');
            $table->string('account_reference')->nullable();
            $table->string('status')->default('active')->index();
            $table->json('provider_metadata')->nullable();
            $table->timestamps();
            $table->unique(['parent_funding_provider_id', 'affiliate_id', 'bank_code'], 'settlement_va_provider_affiliate_bank_unique');
            $table->unique(['parent_funding_provider_id', 'account_number'], 'settlement_va_provider_account_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_settlement_virtual_accounts');
    }
};
