<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('affiliates', 'customer_ui_default')) {
            Schema::table('affiliates', function (Blueprint $table): void {
                $table->string('customer_ui_default', 10)->default('v1');
            });
        }

        if (! Schema::hasColumn('users', 'customer_ui_version')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('customer_ui_version', 10)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'customer_ui_version')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('customer_ui_version'));
        }

        if (Schema::hasColumn('affiliates', 'customer_ui_default')) {
            Schema::table('affiliates', fn (Blueprint $table) => $table->dropColumn('customer_ui_default'));
        }
    }
};
