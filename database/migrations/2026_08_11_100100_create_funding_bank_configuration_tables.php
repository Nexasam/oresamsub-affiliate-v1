<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_funding_providers', function (Blueprint $table) {
            $table->uuid('webhook_key')->nullable()->unique()->after('settings');
            $table->text('webhook_secret')->nullable()->after('webhook_key');
            $table->boolean('webhook_active')->default(false)->after('webhook_secret');
        });

        Schema::table('affiliate_funding_provider_configs', function (Blueprint $table) {
            $table->uuid('webhook_key')->nullable()->unique()->after('bank_codes');
            $table->text('webhook_secret')->nullable()->after('webhook_key');
            $table->boolean('webhook_active')->default(false)->after('webhook_secret');
        });

        DB::table('parent_funding_providers')->whereNull('webhook_key')->orderBy('id')->eachById(fn ($record) => DB::table('parent_funding_providers')->where('id', $record->id)->update(['webhook_key' => (string) Str::uuid()]));
        DB::table('affiliate_funding_provider_configs')->whereNull('webhook_key')->orderBy('id')->eachById(fn ($record) => DB::table('affiliate_funding_provider_configs')->where('id', $record->id)->update(['webhook_key' => (string) Str::uuid()]));

        Schema::create('parent_funding_provider_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_funding_provider_id')->constrained(indexName: 'parent_funding_bank_provider_fk')->cascadeOnDelete();
            $table->string('name');
            $table->string('bank_code');
            $table->text('description')->nullable();
            $table->string('rate_type')->default('flat');
            $table->decimal('rate_value', 14, 2)->default(0);
            $table->decimal('percentage_cap', 14, 2)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->boolean('generation_enabled')->default(true)->index();
            $table->timestamps();
            $table->unique(['parent_funding_provider_id', 'bank_code'], 'parent_funding_bank_code_unique');
        });

        Schema::create('affiliate_funding_provider_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_funding_provider_config_id')->constrained(indexName: 'affiliate_funding_bank_config_fk')->cascadeOnDelete();
            $table->foreignId('parent_funding_provider_bank_id')->constrained(indexName: 'affiliate_funding_parent_bank_fk')->cascadeOnDelete();
            $table->string('rate_type')->default('flat');
            $table->decimal('rate_value', 14, 2)->default(0);
            $table->decimal('percentage_cap', 14, 2)->nullable();
            $table->boolean('active')->default(false)->index();
            $table->boolean('generation_enabled')->default(false)->index();
            $table->timestamps();
            $table->unique(['affiliate_funding_provider_config_id', 'parent_funding_provider_bank_id'], 'affiliate_funding_bank_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_funding_provider_banks');
        Schema::dropIfExists('parent_funding_provider_banks');
        Schema::table('affiliate_funding_provider_configs', function (Blueprint $table) {
            $table->dropColumn(['webhook_key', 'webhook_secret', 'webhook_active']);
        });
        Schema::table('parent_funding_providers', function (Blueprint $table) {
            $table->dropColumn(['webhook_key', 'webhook_secret', 'webhook_active']);
        });
    }
};
