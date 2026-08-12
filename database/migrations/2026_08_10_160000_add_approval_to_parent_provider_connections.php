<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeTableEngine('admins');
        $this->normalizeTableEngine('parent_provider_connections');

        $this->ensureColumn('parent_provider_connections', 'approval_status', fn (Blueprint $table) => $table->string('approval_status')->default('approved')->index()->after('status'));
        $this->ensureColumn('parent_provider_connections', 'submitted_at', fn (Blueprint $table) => $table->timestamp('submitted_at')->nullable()->after('approval_status'));
        $this->ensureColumn('parent_provider_connections', 'approved_at', fn (Blueprint $table) => $table->timestamp('approved_at')->nullable()->after('submitted_at'));
        $this->ensureColumn('parent_provider_connections', 'approved_by_admin_id', fn (Blueprint $table) => $table->foreignId('approved_by_admin_id')->nullable()->after('approved_at'));
        $this->ensureColumn('parent_provider_connections', 'rejection_reason', fn (Blueprint $table) => $table->text('rejection_reason')->nullable()->after('approved_by_admin_id'));

        if (! $this->constraintExists('parent_provider_connections', 'parent_provider_connections_approved_by_admin_id_foreign')) {
            Schema::table('parent_provider_connections', function (Blueprint $table) {
                $table->foreign('approved_by_admin_id', 'parent_provider_connections_approved_by_admin_id_foreign')
                    ->references('id')->on('admins')->nullOnDelete();
            });
        }

        DB::table('parent_provider_connections')
            ->whereNull('submitted_at')
            ->update(['submitted_at' => DB::raw('updated_at')]);
        DB::table('parent_provider_connections')
            ->whereNull('approved_at')
            ->update(['approved_at' => DB::raw('updated_at')]);
    }

    private function ensureColumn(string $table, string $column, callable $definition): void
    {
        if (! Schema::hasColumn($table, $column)) {
            Schema::table($table, $definition);
        }
    }

    private function normalizeTableEngine(string $table): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! Schema::hasTable($table)) {
            return;
        }

        $engine = DB::table('information_schema.TABLES')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->value('ENGINE');

        if (strcasecmp((string) $engine, 'InnoDB') !== 0) {
            DB::statement("ALTER TABLE `{$table}` ENGINE=InnoDB");
        }
    }

    private function constraintExists(string $table, string $name): bool
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return false;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->whereRaw('CONSTRAINT_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->exists();
    }

    public function down(): void
    {
        Schema::table('parent_provider_connections', function (Blueprint $table) {
            $table->dropForeign(['approved_by_admin_id']);
            $table->dropColumn(['approval_status', 'submitted_at', 'approved_at', 'approved_by_admin_id', 'rejection_reason']);
        });
    }
};
