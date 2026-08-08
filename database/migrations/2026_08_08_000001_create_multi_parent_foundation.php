<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('parent_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_business_id')
                ->constrained('parent_businesses')
                ->restrictOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('provider_adapters', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('driver');
            $table->json('capabilities')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('provider_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_business_id')
                ->constrained('parent_businesses')
                ->restrictOnDelete();
            $table->foreignId('provider_adapter_id')
                ->constrained('provider_adapters')
                ->restrictOnDelete();
            $table->string('name');
            $table->string('base_url')->nullable();
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();

            $table->unique(['parent_business_id', 'name']);
        });

        Schema::table('affiliates', function (Blueprint $table) {
            $table->foreignId('parent_business_id')
                ->nullable()
                ->after('id')
                ->constrained('parent_businesses')
                ->restrictOnDelete();
            $table->foreignId('provider_connection_id')
                ->nullable()
                ->after('parent_business_id')
                ->constrained('provider_connections')
                ->restrictOnDelete();
        });

        Schema::create('affiliate_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')
                ->unique()
                ->constrained('affiliates')
                ->restrictOnDelete();
            $table->foreignId('parent_business_id')
                ->constrained('parent_businesses')
                ->restrictOnDelete();
            $table->string('status')->default('pending')->index();
            $table->decimal('purchase_amount', 14, 2)->default(0);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();
        });

        Schema::table('product_plans', function (Blueprint $table) {
            $table->foreignId('parent_business_id')
                ->nullable()
                ->after('id')
                ->constrained('parent_businesses')
                ->restrictOnDelete();
            $table->foreignId('provider_connection_id')
                ->nullable()
                ->after('parent_business_id')
                ->constrained('provider_connections')
                ->restrictOnDelete();
            $table->string('upstream_code')->nullable()->after('api_id');
            $table->decimal('provider_cost', 14, 2)->nullable()->after('cost_price');
            $table->string('status')->default('active')->index();
            $table->json('provider_settings')->nullable();
            $table->json('raw_metadata')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->unique(
                ['provider_connection_id', 'upstream_code'],
                'product_plans_connection_upstream_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('product_plans', function (Blueprint $table) {
            $table->dropUnique('product_plans_connection_upstream_unique');
            $table->dropConstrainedForeignId('provider_connection_id');
            $table->dropConstrainedForeignId('parent_business_id');
            $table->dropColumn([
                'upstream_code',
                'provider_cost',
                'status',
                'provider_settings',
                'raw_metadata',
                'last_synced_at',
            ]);
        });

        Schema::dropIfExists('affiliate_licenses');

        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('provider_connection_id');
            $table->dropConstrainedForeignId('parent_business_id');
        });

        Schema::dropIfExists('provider_connections');
        Schema::dropIfExists('provider_adapters');
        Schema::dropIfExists('parent_admins');
        Schema::dropIfExists('parent_businesses');
    }
};
