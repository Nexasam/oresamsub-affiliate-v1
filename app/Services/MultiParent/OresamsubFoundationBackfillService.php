<?php

namespace App\Services\MultiParent;

use App\Models\MultiParentMigrationAudit;
use App\Models\ParentBusiness;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class OresamsubFoundationBackfillService
{
    public function consolidateLegacyCustomerLevels(ParentBusiness $parent, string $batchUuid): int
    {
        $this->assertNoCrossAffiliateUserPlans($parent);
        $moved = 0;

        DB::table('affiliates')->where('parent_business_id', $parent->id)->orderBy('id')
            ->each(function (object $affiliate) use ($batchUuid, &$moved): void {
                DB::transaction(function () use ($affiliate, $batchUuid, &$moved): void {
                    DB::table('affiliate_user_plans')
                        ->where('affiliate_id', $affiliate->id)
                        ->whereRaw('CAST(plan_level AS UNSIGNED) BETWEEN 1 AND 6')
                        ->lockForUpdate()->get()
                        ->groupBy(fn (object $plan): int => (int) $plan->plan_level)
                        ->each(function ($plans): void {
                            $preferred = $plans->sort(function (object $left, object $right): int {
                                return ((int) $right->visibility <=> (int) $left->visibility)
                                    ?: ((int) $right->is_default <=> (int) $left->is_default)
                                    ?: ($left->id <=> $right->id);
                            })->first();
                            $current = $plans->firstWhere('canonical_plan_level', (int) $preferred->plan_level);

                            if ($current && $current->id !== $preferred->id) {
                                DB::table('affiliate_user_plans')->where('id', $current->id)
                                    ->update(['canonical_plan_level' => null, 'updated_at' => now()]);
                            }
                            DB::table('affiliate_user_plans')->where('id', $preferred->id)->update([
                                'canonical_plan_level' => (int) $preferred->plan_level,
                                'visibility' => 1,
                                'updated_at' => now(),
                            ]);
                        });

                    $levelSix = DB::table('affiliate_user_plans')
                        ->where('affiliate_id', $affiliate->id)
                        ->where('canonical_plan_level', 6)
                        ->lockForUpdate()
                        ->first();

                    if (! $levelSix) {
                        $source = DB::table('user_plans')->where('plan_level', 6)->first();
                        $levelSixId = DB::table('affiliate_user_plans')->insertGetId([
                            'affiliate_id' => $affiliate->id,
                            'user_plan_name' => $source?->user_plan_name ?? 'Diamond Plan',
                            'updated_user_plan_name' => $source?->updated_user_plan_name,
                            'plan_level' => 6,
                            'canonical_plan_level' => 6,
                            'is_default' => $source?->is_default,
                            'visibility' => $source?->visibility ?? 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $levelSixId = $levelSix->id;
                    }

                    $plansToConsolidate = DB::table('affiliate_user_plans')
                        ->where('affiliate_id', $affiliate->id)
                        ->where(function ($query): void {
                            $query->whereRaw('CAST(plan_level AS UNSIGNED) > 6')
                                ->orWhere(function ($query): void {
                                    $query->whereRaw('CAST(plan_level AS UNSIGNED) BETWEEN 1 AND 6')
                                        ->whereNull('canonical_plan_level');
                                });
                        })
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                    foreach ($plansToConsolidate as $oldPlan) {
                        $isLegacy = (int) $oldPlan->plan_level > 6;
                        $canonicalPlanId = $isLegacy
                            ? $levelSixId
                            : DB::table('affiliate_user_plans')
                                ->where('affiliate_id', $affiliate->id)
                                ->where('canonical_plan_level', (int) $oldPlan->plan_level)
                                ->value('id');

                        if (! $canonicalPlanId) {
                            throw new RuntimeException("Affiliate {$affiliate->id} has no canonical customer plan for level {$oldPlan->plan_level}.");
                        }

                        $users = DB::table('users')
                            ->where('affiliate_id', $affiliate->id)
                            ->where('user_plan_id', $oldPlan->id)
                            ->lockForUpdate()
                            ->get();

                        foreach ($users as $user) {
                            $action = $isLegacy
                                ? 'customer_plan_consolidated_to_level_6'
                                : 'duplicate_affiliate_user_plan_consolidated';
                            $deterministicKey = ($isLegacy ? 'customer-plan-consolidation' : 'customer-plan-canonicalization')
                                .":{$user->id}:{$oldPlan->id}";

                            DB::table('users')->where('id', $user->id)->update([
                                'user_plan_id' => $canonicalPlanId,
                                'updated_at' => now(),
                            ]);
                            MultiParentMigrationAudit::firstOrCreate(
                                ['deterministic_key' => $deterministicKey],
                                [
                                    'batch_uuid' => $batchUuid, 'action' => $action,
                                    'entity_type' => 'user', 'entity_id' => $user->id,
                                    'from_value' => json_encode(['user_plan_id' => $oldPlan->id]),
                                    'to_value' => json_encode(['user_plan_id' => $canonicalPlanId]),
                                    'metadata' => ['source' => 'oresamsub_foundation_backfill'],
                                ]
                            );

                            $moved++;
                        }

                        if (! $isLegacy) {
                            $planAuditKey = "affiliate-plan-canonicalization:{$oldPlan->id}:{$canonicalPlanId}";
                            MultiParentMigrationAudit::firstOrCreate(
                                ['deterministic_key' => $planAuditKey],
                                [
                                    'batch_uuid' => $batchUuid,
                                    'action' => 'duplicate_affiliate_user_plan_consolidated',
                                    'entity_type' => 'affiliate_user_plan',
                                    'entity_id' => $oldPlan->id,
                                    'from_value' => json_encode(['affiliate_user_plan_id' => $oldPlan->id]),
                                    'to_value' => json_encode(['affiliate_user_plan_id' => $canonicalPlanId]),
                                    'metadata' => ['source' => 'oresamsub_foundation_backfill'],
                                ]
                            );
                        }

                        DB::table('affiliate_user_plans')->where('id', $oldPlan->id)->update([
                            'visibility' => 0,
                            'updated_at' => now(),
                        ]);
                    }
                });
            });

        return $moved;
    }

    private function assertNoCrossAffiliateUserPlans(ParentBusiness $parent): void
    {
        $corruptUser = DB::table('users')
            ->join('affiliates', 'affiliates.id', '=', 'users.affiliate_id')
            ->join('affiliate_user_plans', 'affiliate_user_plans.id', '=', 'users.user_plan_id')
            ->where(function ($query) use ($parent): void {
                $query->where('affiliates.parent_business_id', $parent->id)
                    ->orWhere(function ($query): void {
                        $query->whereNull('affiliates.parent_business_id')
                            ->whereNull('affiliates.parent_reseller_level_id');
                    });
            })
            ->whereColumn('users.affiliate_id', '!=', 'affiliate_user_plans.affiliate_id')
            ->select('users.id')->orderBy('users.id')->first();

        if ($corruptUser) {
            throw new RuntimeException("User {$corruptUser->id} references a customer plan owned by another affiliate.");
        }
    }

    /** @return array<string, int> */
    public function run(bool $dryRun): array
    {
        $counts = array_fill_keys(['parents', 'affiliates', 'customer_plans', 'plans', 'prices', 'routes', 'transactions', 'audits'], 0);
        $batchUuid = (string) Str::uuid();

        DB::beginTransaction();

        try {
            $context = $this->resolveContext();
            $this->assertNoPartialOwnership();
            $this->assertNoCrossAffiliateUserPlans(ParentBusiness::findOrFail($context['parent']));
            $this->backfillAffiliates($context, $batchUuid, $counts);
            $auditCount = DB::table('multi_parent_migration_audits')->count();
            $counts['customer_plans'] = $this->consolidateLegacyCustomerLevels(
                ParentBusiness::findOrFail($context['parent']),
                $batchUuid
            );
            $counts['audits'] += DB::table('multi_parent_migration_audits')->count() - $auditCount;
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

    private function difference(mixed $higher, mixed $lower): ?string
    {
        if (! is_numeric($higher) || ! is_numeric($lower)) {
            return null;
        }

        return (string) BigDecimal::of((string) $higher)
            ->minus(BigDecimal::of((string) $lower))
            ->toScale(2, RoundingMode::HalfUp);
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
