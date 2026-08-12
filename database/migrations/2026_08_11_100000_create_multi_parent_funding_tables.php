<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('funding_providers')) Schema::create('funding_providers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('adapter_key')->unique();
            $table->json('credential_fields')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        if (! Schema::hasTable('parent_funding_providers')) Schema::create('parent_funding_providers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
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

        if (! Schema::hasTable('affiliate_funding_provider_configs')) Schema::create('affiliate_funding_provider_configs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
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

        if (! Schema::hasTable('funding_mode_change_requests')) Schema::create('funding_mode_change_requests', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('affiliate_funding_provider_config_id')->constrained(indexName: 'funding_mode_config_fk')->cascadeOnDelete();
            $table->string('requested_mode');
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by_parent_admin_id')->nullable()->constrained('parent_admins')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        if (! Schema::hasTable('funding_webhook_events')) Schema::create('funding_webhook_events', function (Blueprint $table) {
            $table->engine = 'InnoDB';
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

        foreach (['funding_providers', 'parent_funding_providers', 'affiliate_funding_provider_configs', 'funding_mode_change_requests', 'funding_webhook_events', 'user_virtual_accounts'] as $table) {
            $this->normalizeTableEngine($table);
        }

        $this->ensureForeign('parent_funding_providers', 'parent_funding_providers_parent_business_id_foreign', 'parent_business_id', 'parent_businesses', 'restrict');
        $this->ensureForeign('parent_funding_providers', 'parent_funding_providers_funding_provider_id_foreign', 'funding_provider_id', 'funding_providers', 'restrict');
        $this->ensureForeign('affiliate_funding_provider_configs', 'affiliate_funding_provider_configs_affiliate_id_foreign', 'affiliate_id', 'affiliates', 'restrict');
        $this->ensureForeign('affiliate_funding_provider_configs', 'affiliate_funding_parent_provider_fk', 'parent_funding_provider_id', 'parent_funding_providers', 'restrict');
        $this->ensureForeign('funding_mode_change_requests', 'funding_mode_config_fk', 'affiliate_funding_provider_config_id', 'affiliate_funding_provider_configs', 'cascade');
        $this->ensureForeign('funding_mode_change_requests', 'funding_mode_change_requests_reviewed_by_parent_admin_id_foreign', 'reviewed_by_parent_admin_id', 'parent_admins', 'null');
        $this->ensureForeign('funding_webhook_events', 'funding_webhook_events_funding_provider_id_foreign', 'funding_provider_id', 'funding_providers', 'restrict');
        $this->ensureForeign('funding_webhook_events', 'funding_webhook_events_parent_business_id_foreign', 'parent_business_id', 'parent_businesses', 'null');
        $this->ensureForeign('funding_webhook_events', 'funding_webhook_events_affiliate_id_foreign', 'affiliate_id', 'affiliates', 'null');
        $this->ensureForeign('funding_webhook_events', 'funding_webhook_config_fk', 'affiliate_funding_provider_config_id', 'affiliate_funding_provider_configs', 'null');

        $this->ensureColumn('user_virtual_accounts', 'parent_business_id', fn (Blueprint $table) => $table->foreignId('parent_business_id')->nullable()->after('affiliate_id'));
        $this->ensureColumn('user_virtual_accounts', 'parent_funding_provider_id', fn (Blueprint $table) => $table->foreignId('parent_funding_provider_id')->nullable()->after('funding_option_id'));
        $this->ensureColumn('user_virtual_accounts', 'affiliate_funding_provider_config_id', fn (Blueprint $table) => $table->foreignId('affiliate_funding_provider_config_id')->nullable()->after('parent_funding_provider_id'));

        $this->ensureForeign('user_virtual_accounts', 'user_virtual_accounts_parent_business_id_foreign', 'parent_business_id', 'parent_businesses', 'null');
        $this->ensureForeign('user_virtual_accounts', 'user_virtual_accounts_parent_funding_provider_id_foreign', 'parent_funding_provider_id', 'parent_funding_providers', 'null');
        $this->ensureForeign('user_virtual_accounts', 'user_virtual_account_funding_config_fk', 'affiliate_funding_provider_config_id', 'affiliate_funding_provider_configs', 'null');
    }

    private function ensureColumn(string $table, string $column, callable $definition): void
    {
        if (! Schema::hasColumn($table, $column)) Schema::table($table, $definition);
    }

    private function normalizeTableEngine(string $table): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! Schema::hasTable($table)) return;
        $engine = DB::table('information_schema.TABLES')->whereRaw('TABLE_SCHEMA = DATABASE()')->where('TABLE_NAME', $table)->value('ENGINE');
        if (strcasecmp((string) $engine, 'InnoDB') !== 0) DB::statement("ALTER TABLE `{$table}` ENGINE=InnoDB");
    }

    private function ensureForeign(string $table, string $name, string $column, string $foreignTable, string $onDelete): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') return;
        if ($this->constraintExists($table, $name)) return;
        Schema::table($table, function (Blueprint $blueprint) use ($column, $name, $foreignTable, $onDelete) {
            $foreign = $blueprint->foreign($column, $name)->references('id')->on($foreignTable);
            match ($onDelete) {
                'cascade' => $foreign->cascadeOnDelete(),
                'null' => $foreign->nullOnDelete(),
                default => $foreign->restrictOnDelete(),
            };
        });
    }

    private function constraintExists(string $table, string $name): bool
    {
        if (DB::connection()->getDriverName() === 'sqlite') return false;
        return DB::table('information_schema.TABLE_CONSTRAINTS')->whereRaw('CONSTRAINT_SCHEMA = DATABASE()')->where('TABLE_NAME', $table)->where('CONSTRAINT_NAME', $name)->exists();
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
