<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funding_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('adapter_key')->unique();
            $table->json('credential_fields')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('parent_funding_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_business_id')->constrained()->restrictOnDelete();
            $table->foreignId('funding_provider_id')->constrained()->restrictOnDelete();
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('active')->default(false)->index();
            $table->boolean('generation_enabled')->default(false)->index();
            $table->timestamps();
            $table->unique(['parent_business_id', 'funding_provider_id'], 'parent_funding_provider_unique');
        });

        Schema::create('affiliate_funding_provider_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_funding_provider_id')->constrained(indexName: 'affiliate_funding_parent_provider_fk')->restrictOnDelete();
            $table->string('management_mode')->default('parent_managed')->index();
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->json('bank_codes')->nullable();
            $table->boolean('active')->default(false)->index();
            $table->boolean('generation_enabled')->default(false)->index();
            $table->timestamps();
            $table->unique(['affiliate_id', 'parent_funding_provider_id'], 'affiliate_parent_funding_unique');
        });

        Schema::create('funding_mode_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_funding_provider_config_id')->constrained(indexName: 'funding_mode_config_fk')->cascadeOnDelete();
            $table->string('requested_mode');
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by_parent_admin_id')->nullable()->constrained('parent_admins')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('funding_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funding_provider_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_business_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('affiliate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('affiliate_funding_provider_config_id')->nullable()->constrained(indexName: 'funding_webhook_config_fk')->nullOnDelete();
            $table->string('external_event_id');
            $table->json('payload');
            $table->string('status')->default('received')->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['funding_provider_id', 'external_event_id'], 'funding_webhook_event_unique');
        });

        Schema::table('user_virtual_accounts', function (Blueprint $table) {
            $table->foreignId('parent_business_id')->nullable()->after('affiliate_id')->constrained()->nullOnDelete();
            $table->foreignId('parent_funding_provider_id')->nullable()->after('funding_option_id')->constrained()->nullOnDelete();
            $table->foreignId('affiliate_funding_provider_config_id')->nullable()->after('parent_funding_provider_id')->constrained(indexName: 'user_virtual_account_funding_config_fk')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_virtual_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('affiliate_funding_provider_config_id');
            $table->dropConstrainedForeignId('parent_funding_provider_id');
            $table->dropConstrainedForeignId('parent_business_id');
        });
        Schema::dropIfExists('funding_webhook_events');
        Schema::dropIfExists('funding_mode_change_requests');
        Schema::dropIfExists('affiliate_funding_provider_configs');
        Schema::dropIfExists('parent_funding_providers');
        Schema::dropIfExists('funding_providers');
    }
};
