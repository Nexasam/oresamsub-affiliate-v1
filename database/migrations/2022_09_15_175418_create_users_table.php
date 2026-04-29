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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->foreignId('affiliate_id')->index();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('pin')->default(1234)->nullable();
            $table->string('other_names')->nullable();
            $table->string('upline_id')->nullable();
            $table->string('new_user_alert')->default(0);
            $table->string('api_token')->unique()->nullable();
            $table->string('user_monnify_reference')->nullable();
            $table->string('is_bvn_verified')->default(0);
            $table->string('is_nin_verified')->default(0);
            $table->string('account_tier')->default(0);
            $table->string('is_deactivated')->default(0);
            $table->string('customer_category')->default('generic')->comment('it can be pos too');
            $table->string('customer_landmark')->nullable();
            $table->string('bvn')->nullable();
            $table->string('verification_attempts')->default(0); #should not be more than 3 times
            $table->string('verification_status')->default(0); #should not be more than 3 times
            $table->string('bvn_json')->nullable();
            $table->string('nin')->nullable();
            $table->string('nin_json')->nullable();
            $table->boolean('phone_verification')->default(false);
            $table->string('termii_pin_id')->nullable();
            $table->longText('termii_json')->nullable();
            $table->string('fingerprint_option')->default('0');
            $table->string('phone_number')->nullable()->unique();
            $table->string('default_wallet_setting')->default('main_wallet')->comment('main_wallet / bulk_data_wallet');
            $table->string('user_2fa_setting')->default('OFF')->comment('ON / OFF');
            $table->string('main_wallet')->default(0)->nullable();
            $table->string('role_id');
            $table->foreignId('user_plan_id')->nullable()->constrained('affiliate_user_plans');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('active')->default(1)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
            Schema::dropIfExists('password_reset_tokens');
            Schema::dropIfExists('sessions');
    }
};
