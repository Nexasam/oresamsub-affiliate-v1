<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Drop existing unique indexes
            $table->dropUnique(['email']);

            // Uncomment ONLY if phone_number is currently unique
            $table->dropUnique(['phone_number']);

            // Add tenant-based unique indexes
            $table->unique(['affiliate_id', 'email']);

            $table->unique(['affiliate_id', 'phone_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Drop composite indexes
            $table->dropUnique(['affiliate_id', 'email']);

            $table->dropUnique(['affiliate_id', 'phone_number']);

            // Restore global uniqueness
            $table->unique('email');

            $table->unique('phone_number');
        });
    }
};