<?php

namespace App\Services\MultiParent;

use App\Models\ParentBusiness;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class OresamsubFoundationBackfillService
{
    public function consolidateLegacyCustomerLevels(ParentBusiness $parent, string $batchUuid): int
    {
        $moved = 0;

        DB::table('affiliates')->where('parent_business_id', $parent->id)->orderBy('id')
            ->each(function (object $affiliate) use ($batchUuid, &$moved): void {
                DB::transaction(function () use ($affiliate, $batchUuid, &$moved): void {
                    $levelSix = DB::table('affiliate_user_plans')
                        ->where('affiliate_id', $affiliate->id)
                        ->where('plan_level', 6)
                        ->lockForUpdate()
                        ->first();

                    if (! $levelSix) {
                        $source = DB::table('user_plans')->where('plan_level', 6)->first();
                        $levelSixId = DB::table('affiliate_user_plans')->insertGetId([
                            'affiliate_id' => $affiliate->id,
                            'user_plan_name' => $source?->user_plan_name ?? 'Diamond Plan',
                            'updated_user_plan_name' => $source?->updated_user_plan_name,
                            'plan_level' => 6,
                            'is_default' => $source?->is_default,
                            'visibility' => $source?->visibility ?? 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $levelSixId = $levelSix->id;
                    }

                    $legacyPlans = DB::table('affiliate_user_plans')
                        ->where('affiliate_id', $affiliate->id)
                        ->whereRaw('CAST(plan_level AS UNSIGNED) > 6')
                        ->lockForUpdate()
                        ->get();

                    foreach ($legacyPlans as $legacyPlan) {
                        $users = DB::table('users')
                            ->where('affiliate_id', $affiliate->id)
                            ->where('user_plan_id', $legacyPlan->id)
                            ->lockForUpdate()
                            ->get();

                        foreach ($users as $user) {
                            $uniquenessKey = "customer-plan-consolidation:{$user->id}:{$legacyPlan->id}";
                            $alreadyAudited = DB::table('multi_parent_migration_audits')
                                ->where('action', 'customer_plan_consolidated_to_level_6')
                                ->where('entity_type', 'user')
                                ->where('entity_id', $user->id)
                                ->get(['metadata'])
                                ->contains(fn (object $audit): bool => data_get(json_decode($audit->metadata, true), 'uniqueness_key') === $uniquenessKey
                                );

                            DB::table('users')->where('id', $user->id)->update([
                                'user_plan_id' => $levelSixId,
                                'updated_at' => now(),
                            ]);

                            if (! $alreadyAudited) {
                                DB::table('multi_parent_migration_audits')->insert([
                                    'batch_uuid' => $batchUuid,
                                    'action' => 'customer_plan_consolidated_to_level_6',
                                    'entity_type' => 'user',
                                    'entity_id' => $user->id,
                                    'from_value' => json_encode(['user_plan_id' => $legacyPlan->id]),
                                    'to_value' => json_encode(['user_plan_id' => $levelSixId]),
                                    'metadata' => json_encode([
                                        'source' => 'oresamsub_foundation_backfill',
                                        'uniqueness_key' => $uniquenessKey,
                                    ]),
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }

                            $moved++;
                        }

                        DB::table('affiliate_user_plans')->where('id', $legacyPlan->id)->update([
                            'visibility' => 0,
                            'updated_at' => now(),
                        ]);
                    }
                });
            });

        return $moved;
    }

    /** @return array<string, int> */
    public function run(bool $dryRun): array
    {
        $counts = array_fill_keys(['parents', 'affiliates', 'plans', 'prices', 'routes', 'transactions', 'audits'], 0);
        $batchUuid = (string) Str::uuid();

        DB::beginTransaction();

        try {
            $context = $this->resolveContext();
            $this->assertNoPartialOwnership();
            $this->backfillAffiliates($context, $batchUuid, $counts);
            $this->backfillPlans($context, $batchUuid, $counts);
            $this->backfillTransactions($context, $batchUuid, $counts);

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }

            return $counts;
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }
    }

    /** @return array<string, int> */
    private function resolveContext(): array
    {
        $config = config('parent_businesses.oresamsub');
        $parent = DB::table('parent_businesses')->where('slug', $config['slug'])->first();
        $provider = DB::table('provider_connections')->where('slug', $config['provider']['slug'])->first();

        if (! $parent || ! $provider) {
            throw new RuntimeException('Run the OresamSub parent seeder before backfilling.');
        }

        $connection = DB::table('parent_provider_connections')
            ->where('parent_business_id', $parent->id)
            ->where('provider_connection_id', $provider->id)
            ->where('name', $config['provider']['name'])
            ->first();
        $basic = DB::table('parent_reseller_levels')
            ->where('parent_business_id', $parent->id)
            ->where('position', 1)
            ->where('name', 'Basic')
            ->first();

        if (! $connection || ! $basic) {
            throw new RuntimeException('The OresamSub connection and Basic reseller level must exist.');
        }

        return ['parent' => $parent->id, 'provider' => $provider->id, 'connection' => $connection->id, 'basic' => $basic->id];
    }

    private function assertNoPartialOwnership(): void
    {
        DB::table('affiliates')->orderBy('id')->chunkById(250, function ($affiliates) {
            foreach ($affiliates as $affiliate) {
                if (($affiliate->parent_business_id === null) !== ($affiliate->parent_reseller_level_id === null)) {
                    throw new RuntimeException("Affiliate {$affiliate->id} has partial parent ownership.");
                }
            }
        });

        DB::table('transactions')->orderBy('id')->chunkById(250, function ($transactions) {
            foreach ($transactions as $transaction) {
                $ids = [$transaction->parent_business_id, $transaction->parent_provider_connection_id, $transaction->product_plan_provider_route_id];
                $set = count(array_filter($ids, fn ($value) => $value !== null));
                if ($set > 0 && $set < count($ids)) {
                    throw new RuntimeException("Transaction {$transaction->id} has partial parent routing ownership.");
                }
            }
        });
    }

    private function backfillAffiliates(array $context, string $batchUuid, array &$counts): void
    {
        DB::table('affiliates')->whereNull('parent_business_id')->whereNull('parent_reseller_level_id')
            ->orderBy('id')->chunkById(250, function ($affiliates) use ($context, $batchUuid, &$counts) {
                foreach ($affiliates as $affiliate) {
                    DB::table('affiliates')->where('id', $affiliate->id)->update([
                        'parent_business_id' => $context['parent'], 'parent_reseller_level_id' => $context['basic'], 'updated_at' => now(),
                    ]);
                    $counts['affiliates']++;
                    $this->audit($batchUuid, 'claim', 'affiliate', $affiliate->id, null, ['parent_business_id' => $context['parent'], 'parent_reseller_level_id' => $context['basic']], $counts);
                }
            });
    }

    private function backfillPlans(array $context, string $batchUuid, array &$counts): void
    {
        DB::table('product_plans')->orderBy('id')->chunkById(250, function ($plans) use ($context, $batchUuid, &$counts) {
            foreach ($plans as $plan) {
                if ($plan->parent_business_id !== null && (int) $plan->parent_business_id !== $context['parent']) {
                    continue;
                }
                if ($plan->parent_business_id === null) {
                    DB::table('product_plans')->where('id', $plan->id)->update(['parent_business_id' => $context['parent'], 'updated_at' => now()]);
                    $counts['plans']++;
                    $this->audit($batchUuid, 'claim', 'product_plan', $plan->id, null, ['parent_business_id' => $context['parent']], $counts);
                }
                $this->backfillPlanPrices($plan, $context, $batchUuid, $counts);
                $this->backfillPlanRoute($plan, $context, $batchUuid, $counts);
            }
        });
    }

    private function backfillPlanPrices(object $plan, array $context, string $batchUuid, array &$counts): void
    {
        $levels = DB::table('parent_reseller_levels')->where('parent_business_id', $context['parent'])
            ->whereBetween('position', [1, 6])->get()->keyBy('position');
        foreach (range(1, 6) as $position) {
            $value = $plan->{"cost_price_{$position}"};
            if (! is_numeric($value) || ! isset($levels[$position])) {
                continue;
            }
            $exists = DB::table('product_plan_parent_prices')->where('product_plan_id', $plan->id)
                ->where('parent_reseller_level_id', $levels[$position]->id)->exists();
            if ($exists) {
                continue;
            }
            $id = DB::table('product_plan_parent_prices')->insertGetId([
                'parent_business_id' => $context['parent'], 'product_plan_id' => $plan->id,
                'parent_reseller_level_id' => $levels[$position]->id, 'selling_price' => $value,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $counts['prices']++;
            $this->audit($batchUuid, 'create', 'product_plan_parent_price', $id, null, ['product_plan_id' => $plan->id], $counts);
        }
    }

    private function backfillPlanRoute(object $plan, array $context, string $batchUuid, array &$counts): void
    {
        if (DB::table('product_plan_provider_routes')->where('product_plan_id', $plan->id)->where('priority', 1)->exists()) {
            return;
        }
        $providerPlanId = $plan->automation_product_plan_id ?: $plan->api_id ?: "legacy-{$plan->id}";
        $id = DB::table('product_plan_provider_routes')->insertGetId([
            'parent_business_id' => $context['parent'], 'product_plan_id' => $plan->id,
            'parent_provider_connection_id' => $context['connection'], 'provider_plan_id' => $providerPlanId,
            'priority' => 1, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $counts['routes']++;
        $this->audit($batchUuid, 'create', 'product_plan_provider_route', $id, null, ['product_plan_id' => $plan->id], $counts);
    }

    private function backfillTransactions(array $context, string $batchUuid, array &$counts): void
    {
        DB::table('transactions')->orderBy('id')->chunkById(250, function ($transactions) use ($context, $batchUuid, &$counts) {
            foreach ($transactions as $transaction) {
                if ($transaction->parent_business_id !== null && (int) $transaction->parent_business_id !== $context['parent']) {
                    continue;
                }
                $plan = DB::table('affiliate_product_plans')->join('product_plans', 'product_plans.id', '=', 'affiliate_product_plans.product_plan_id')
                    ->where('affiliate_product_plans.id', $transaction->affiliate_product_plan_id)
                    ->select('product_plans.*')->first();
                if (! $plan || (int) $plan->parent_business_id !== $context['parent']) {
                    continue;
                }
                $route = DB::table('product_plan_provider_routes')->where('product_plan_id', $plan->id)->where('priority', 1)->first();
                if (! $route || (int) $route->parent_business_id !== $context['parent']) {
                    continue;
                }
                $providerCost = is_numeric($plan->admin_cost_price) ? $plan->admin_cost_price : null;
                $parentCost = is_numeric($plan->cost_price) ? $plan->cost_price : null;
                $affiliateCost = is_numeric($plan->cost_price_1) ? $plan->cost_price_1 : null;
                $customerPrice = is_numeric($transaction->amount) ? $transaction->amount : null;
                $values = [
                    'parent_business_id' => $context['parent'], 'parent_provider_connection_id' => $context['connection'],
                    'product_plan_provider_route_id' => $route->id, 'provider_plan_id_snapshot' => $route->provider_plan_id,
                    'provider_cost_snapshot' => $providerCost, 'parent_cost_snapshot' => $parentCost,
                    'affiliate_cost_snapshot' => $affiliateCost, 'customer_price_snapshot' => $customerPrice,
                    'parent_profit_snapshot' => $this->difference($affiliateCost, $parentCost),
                    'affiliate_profit_snapshot' => $this->difference($customerPrice, $affiliateCost),
                ];
                $updates = collect($values)
                    ->filter(fn ($value, $column) => $transaction->{$column} === null && $value !== null)
                    ->all();
                if ($updates === []) {
                    continue;
                }
                DB::table('transactions')->where('id', $transaction->id)->update([...$updates, 'updated_at' => now()]);
                $counts['transactions']++;
                $this->audit($batchUuid, 'backfill', 'transaction', $transaction->id, null, ['product_plan_provider_route_id' => $route->id], $counts);
            }
        });
    }

    private function difference(mixed $higher, mixed $lower): ?float
    {
        return is_numeric($higher) && is_numeric($lower) ? (float) $higher - (float) $lower : null;
    }

    private function audit(string $batchUuid, string $action, string $entityType, int $entityId, mixed $from, mixed $to, array &$counts): void
    {
        DB::table('multi_parent_migration_audits')->insert([
            'batch_uuid' => $batchUuid, 'action' => $action, 'entity_type' => $entityType, 'entity_id' => $entityId,
            'from_value' => $from === null ? null : json_encode($from), 'to_value' => json_encode($to),
            'metadata' => json_encode(['source' => 'oresamsub_foundation_backfill']), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $counts['audits']++;
    }
}
