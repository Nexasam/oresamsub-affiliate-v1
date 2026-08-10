<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('provider_connections')
            ->select('adapter')
            ->groupBy('adapter')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('adapter');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('Provider adapter keys must be unique before migrating: '.$duplicates->join(', '));
        }

        Schema::table('provider_connections', function (Blueprint $table) {
            $table->unique('adapter', 'provider_connections_adapter_unique');
        });
    }

    public function down(): void
    {
        Schema::table('provider_connections', function (Blueprint $table) {
            $table->dropUnique('provider_connections_adapter_unique');
        });
    }
};
