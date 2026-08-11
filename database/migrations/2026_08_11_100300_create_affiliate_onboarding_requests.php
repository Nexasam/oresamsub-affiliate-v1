<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_onboarding_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_business_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by_parent_admin_id')->constrained('parent_admins')->restrictOnDelete();
            $table->foreignId('parent_reseller_level_id')->constrained()->restrictOnDelete();
            $table->foreignId('affiliate_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('request_type')->index();
            $table->string('requested_name')->nullable();
            $table->string('requested_slug')->nullable()->index();
            $table->string('requested_email')->nullable();
            $table->string('requested_phone')->nullable();
            $table->string('requested_domain')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_onboarding_requests');
    }
};
