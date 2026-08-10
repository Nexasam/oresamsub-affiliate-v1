<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_provider_connections', function (Blueprint $table) {
            $table->string('approval_status')->default('approved')->index()->after('status');
            $table->timestamp('submitted_at')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->foreignId('approved_by_admin_id')->nullable()->after('approved_at')->constrained('admins')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('approved_by_admin_id');
        });

        DB::table('parent_provider_connections')->update([
            'submitted_at' => DB::raw('updated_at'),
            'approved_at' => DB::raw('updated_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('parent_provider_connections', function (Blueprint $table) {
            $table->dropForeign(['approved_by_admin_id']);
            $table->dropColumn(['approval_status', 'submitted_at', 'approved_at', 'approved_by_admin_id', 'rejection_reason']);
        });
    }
};
