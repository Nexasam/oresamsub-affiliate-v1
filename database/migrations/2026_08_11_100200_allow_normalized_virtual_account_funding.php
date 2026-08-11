<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_virtual_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('funding_option_id')->nullable()->change();
            $table->unique(
                ['affiliate_funding_provider_config_id', 'user_id', 'bank_code'],
                'user_va_funding_config_user_bank_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('user_virtual_accounts', function (Blueprint $table) {
            $table->dropUnique('user_va_funding_config_user_bank_unique');
        });
    }
};
