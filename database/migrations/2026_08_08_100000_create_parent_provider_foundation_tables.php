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
            $table->foreignId('parent_business_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('active')->default(true);
            $table->boolean('must_change_password')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('provider_connections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('adapter');
            $table->json('capabilities')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('parent_provider_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_business_id')->constrained()->restrictOnDelete();
            $table->foreignId('provider_connection_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('base_url')->nullable();
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
            $table->unique(
                ['parent_business_id', 'provider_connection_id', 'name'],
                'parent_provider_connection_identity_unique'
            );
            $table->unique(['id', 'parent_business_id']);
        });

        Schema::create('parent_reseller_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_business_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('position');
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->unique(['parent_business_id', 'position']);
            $table->unique(['id', 'parent_business_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_reseller_levels');
        Schema::dropIfExists('parent_provider_connections');
        Schema::dropIfExists('provider_connections');
        Schema::dropIfExists('parent_admins');
        Schema::dropIfExists('parent_businesses');
    }
};
