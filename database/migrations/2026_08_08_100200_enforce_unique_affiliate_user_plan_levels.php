<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $batchUuid = (string) Str::uuid();

        DB::table('affiliate_user_plans')
            ->select('affiliate_id', 'plan_level')
            ->groupBy('affiliate_id', 'plan_level')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('affiliate_id')
            ->get()
            ->each(function (object $duplicate) use ($batchUuid): void {
                $plans = DB::table('affiliate_user_plans')
                    ->where('affiliate_id', $duplicate->affiliate_id)
                    ->where('plan_level', $duplicate->plan_level)
                    ->orderBy('id')
                    ->get();
                $canonical = $plans->shift();

                foreach ($plans as $plan) {
                    DB::table('users')->where('affiliate_id', $duplicate->affiliate_id)
                        ->where('user_plan_id', $plan->id)->update(['user_plan_id' => $canonical->id]);
                    DB::table('multi_parent_migration_audits')->insert([
                        'batch_uuid' => $batchUuid,
                        'action' => 'duplicate_affiliate_user_plan_resolved',
                        'entity_type' => 'affiliate_user_plan',
                        'entity_id' => $plan->id,
                        'from_value' => json_encode(['affiliate_user_plan_id' => $plan->id]),
                        'to_value' => json_encode(['affiliate_user_plan_id' => $canonical->id]),
                        'metadata' => json_encode(['affiliate_id' => $duplicate->affiliate_id, 'plan_level' => $duplicate->plan_level]),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    DB::table('affiliate_user_plans')->where('id', $plan->id)->delete();
                }
            });

        Schema::table('affiliate_user_plans', function (Blueprint $table): void {
            $table->unique(['affiliate_id', 'plan_level'], 'affiliate_user_plans_affiliate_level_unique');
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_user_plans', function (Blueprint $table): void {
            $table->dropUnique('affiliate_user_plans_affiliate_level_unique');
        });
    }
};
