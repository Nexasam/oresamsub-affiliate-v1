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
            $table->foreignId('parent_business_id');
            $table->foreignId('requested_by_parent_admin_id');
            $table->foreignId('parent_reseller_level_id');
            $table->foreignId('affiliate_id')->nullable();
            $table->string('request_type')->index();
            $table->string('requested_name')->nullable();
            $table->string('requested_slug')->nullable()->index();
            $table->string('requested_email')->nullable();
            $table->string('requested_phone')->nullable();
            $table->string('requested_domain')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by_admin_id')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('parent_business_id', 'aor_parent_business_fk')
                ->references('id')->on('parent_businesses')->restrictOnDelete();
            $table->foreign('requested_by_parent_admin_id', 'aor_requester_fk')
                ->references('id')->on('parent_admins')->restrictOnDelete();
            $table->foreign('parent_reseller_level_id', 'aor_reseller_level_fk')
                ->references('id')->on('parent_reseller_levels')->restrictOnDelete();
            $table->foreign('affiliate_id', 'aor_affiliate_fk')
                ->references('id')->on('affiliates')->restrictOnDelete();
            $table->foreign('reviewed_by_admin_id', 'aor_reviewer_fk')
                ->references('id')->on('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_onboarding_requests');
    }
};
