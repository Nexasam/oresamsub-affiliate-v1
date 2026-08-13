<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'provider_response')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->longText('provider_response')->nullable()->after('admin_screen_message');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'provider_response')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('provider_response');
            });
        }
    }
};
