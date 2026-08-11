<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_processing_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_business_id');
            $table->foreignId('affiliate_id');
            $table->string('management_mode')->default('parent_managed')->index();
            $table->string('processing_engine')->default('multi_parent')->index();
            $table->foreignId('parent_provider_connection_id')->nullable();
            $table->text('credentials')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->unique('affiliate_id', 'app_affiliate_unique');
            $table->foreign('parent_business_id', 'app_parent_fk')->references('id')->on('parent_businesses')->restrictOnDelete();
            $table->foreign('affiliate_id', 'app_affiliate_fk')->references('id')->on('affiliates')->restrictOnDelete();
            $table->foreign('parent_provider_connection_id', 'app_connection_fk')->references('id')->on('parent_provider_connections')->restrictOnDelete();
        });

        Schema::create('affiliate_processing_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_processing_profile_id');
            $table->foreignId('parent_business_id');
            $table->foreignId('affiliate_id');
            $table->foreignId('requested_by_parent_admin_id');
            $table->string('requested_management_mode');
            $table->string('requested_processing_engine');
            $table->foreignId('requested_parent_provider_connection_id')->nullable();
            $table->text('credentials')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by_admin_id')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('affiliate_processing_profile_id', 'apcr_profile_fk')->references('id')->on('affiliate_processing_profiles')->restrictOnDelete();
            $table->foreign('parent_business_id', 'apcr_parent_fk')->references('id')->on('parent_businesses')->restrictOnDelete();
            $table->foreign('affiliate_id', 'apcr_affiliate_fk')->references('id')->on('affiliates')->restrictOnDelete();
            $table->foreign('requested_by_parent_admin_id', 'apcr_requester_fk')->references('id')->on('parent_admins')->restrictOnDelete();
            $table->foreign('requested_parent_provider_connection_id', 'apcr_connection_fk')->references('id')->on('parent_provider_connections')->restrictOnDelete();
            $table->foreign('reviewed_by_admin_id', 'apcr_reviewer_fk')->references('id')->on('admins')->nullOnDelete();
        });

        $now = now();
        $rows = DB::table('affiliates')
            ->join('parent_businesses', 'parent_businesses.id', '=', 'affiliates.parent_business_id')
            ->whereNotNull('affiliates.parent_business_id')
            ->select('affiliates.id as affiliate_id', 'affiliates.parent_business_id', 'parent_businesses.slug')
            ->get()
            ->map(fn ($row) => [
                'parent_business_id' => $row->parent_business_id,
                'affiliate_id' => $row->affiliate_id,
                'management_mode' => $row->slug === 'oresamsub' ? 'affiliate_managed' : 'parent_managed',
                'processing_engine' => $row->slug === 'oresamsub' ? 'legacy_oresamsub' : 'multi_parent',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

        if ($rows !== []) {
            DB::table('affiliate_processing_profiles')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_processing_change_requests');
        Schema::dropIfExists('affiliate_processing_profiles');
    }
};
