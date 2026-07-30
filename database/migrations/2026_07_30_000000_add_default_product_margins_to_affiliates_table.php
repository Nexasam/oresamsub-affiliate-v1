<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->decimal('default_flat_profit_margin', 12, 2)->default(50)->after('activation_status');
            $table->decimal('default_percent_profit_margin', 5, 2)->default(1)->after('default_flat_profit_margin');
        });
    }

    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropColumn(['default_flat_profit_margin', 'default_percent_profit_margin']);
        });
    }
};
