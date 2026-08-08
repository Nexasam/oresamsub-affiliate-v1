<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_user_plans', function (Blueprint $table): void {
            $table->unsignedTinyInteger('canonical_plan_level')->nullable()->after('plan_level');
        });
        Schema::table('multi_parent_migration_audits', function (Blueprint $table): void {
            $table->string('deterministic_key')->nullable()->unique();
        });

        DB::table('affiliate_user_plans')
            ->whereRaw('CAST(plan_level AS UNSIGNED) BETWEEN 1 AND 6')
            ->orderBy('affiliate_id')->orderByRaw('CAST(plan_level AS UNSIGNED)')->orderBy('id')
            ->get()->groupBy(fn (object $plan): string => "{$plan->affiliate_id}:".(int) $plan->plan_level)
            ->each(function ($plans): void {
                $canonical = $plans->first();
                DB::table('affiliate_user_plans')->where('id', $canonical->id)
                    ->update(['canonical_plan_level' => (int) $canonical->plan_level]);
            });

        Schema::table('affiliate_user_plans', function (Blueprint $table): void {
            $table->unique(['affiliate_id', 'canonical_plan_level'], 'affiliate_user_plans_affiliate_canonical_level_unique');
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_user_plans', function (Blueprint $table): void {
            $table->dropUnique('affiliate_user_plans_affiliate_canonical_level_unique');
            $table->dropColumn('canonical_plan_level');
        });
        Schema::table('multi_parent_migration_audits', function (Blueprint $table): void {
            $table->dropUnique(['deterministic_key']);
            $table->dropColumn('deterministic_key');
        });
    }
};
