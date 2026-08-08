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
            ->select('affiliate_id')->distinct()->orderBy('affiliate_id')
            ->chunkById(250, function ($affiliates): void {
                foreach ($affiliates as $affiliate) {
                    foreach (range(1, 6) as $level) {
                        $canonicalId = DB::table('affiliate_user_plans')
                            ->where('affiliate_id', $affiliate->affiliate_id)
                            ->whereRaw('CAST(plan_level AS UNSIGNED) = ?', [$level])
                            ->orderByRaw('CAST(visibility AS UNSIGNED) DESC')
                            ->orderByRaw('CAST(is_default AS UNSIGNED) DESC')
                            ->orderBy('id')
                            ->value('id');

                        if ($canonicalId !== null) {
                            DB::table('affiliate_user_plans')->where('id', $canonicalId)
                                ->update(['canonical_plan_level' => $level]);
                        }
                    }
                }
            }, 'affiliate_id', 'affiliate_id');

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
