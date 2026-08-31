<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicate = DB::table('users')
            ->select('affiliate_id', 'username')
            ->whereNotNull('username')
            ->groupBy('affiliate_id', 'username')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate) {
            throw new RuntimeException(
                "Cannot enforce affiliate-scoped usernames: affiliate {$duplicate->affiliate_id} has duplicate username '{$duplicate->username}'."
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique(['affiliate_id', 'username'], 'users_affiliate_username_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_affiliate_username_unique');
        });
    }
};
