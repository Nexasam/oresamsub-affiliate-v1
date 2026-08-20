<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'face_value_snapshot')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->decimal('face_value_snapshot', 14, 2)->nullable()->after('customer_price_snapshot');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'face_value_snapshot')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('face_value_snapshot');
            });
        }
    }
};
