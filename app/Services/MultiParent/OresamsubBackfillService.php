<?php

namespace App\Services\MultiParent;

use App\Models\Affiliate;
use App\Models\AffiliateLicense;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ProductPlan;
use App\Models\ProviderAdapter;
use App\Models\ProviderConnection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class OresamsubBackfillService
{
    /**
     * @return array<string, int>
     */
    public function run(
        ?int $sourceAffiliateId = null,
        bool $migrateAdmins = false,
        bool $dryRun = false,
    ): array {
        if ($migrateAdmins && ! $sourceAffiliateId) {
            throw new InvalidArgumentException('A source affiliate is required when migrating admins.');
        }

        DB::beginTransaction();

        try {
            $result = $this->performBackfill($sourceAffiliateId, $migrateAdmins);

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }

            return $result;
        } catch (Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @return array<string, int>
     */
    private function performBackfill(?int $sourceAffiliateId, bool $migrateAdmins): array
    {
        $parent = ParentBusiness::query()->firstOrCreate(
            ['slug' => 'oresamsub'],
            [
                'name' => 'OresamSub',
                'contact_email' => config('mail.from.address'),
                'status' => 'active',
            ],
        );

        $adapter = ProviderAdapter::query()->firstOrCreate(
            ['key' => 'oresamsub'],
            [
                'name' => 'OresamSub Legacy API',
                'driver' => 'oresamsub_legacy',
                'capabilities' => ['data', 'airtime', 'cable', 'electricity'],
                'is_active' => true,
            ],
        );

        $connection = ProviderConnection::query()->firstOrCreate(
            [
                'parent_business_id' => $parent->id,
                'name' => 'Production',
            ],
            [
                'provider_adapter_id' => $adapter->id,
                'base_url' => 'https://oresamsub.com/api/v1',
                'status' => 'active',
            ],
        );

        $this->assertNoPartialLegacyOwnership(Affiliate::query(), 'affiliate');
        $this->assertNoPartialLegacyOwnership(ProductPlan::query(), 'product plan');

        $affiliatesUpdated = Affiliate::query()
            ->whereNull('parent_business_id')
            ->whereNull('provider_connection_id')
            ->update([
                'parent_business_id' => $parent->id,
                'provider_connection_id' => $connection->id,
            ]);

        $licensesCreated = 0;
        Affiliate::query()
            ->where('parent_business_id', $parent->id)
            ->where('provider_connection_id', $connection->id)
            ->select(['id', 'activation_status'])
            ->orderBy('id')->chunkById(250, function ($affiliates) use ($parent, &$licensesCreated) {
                foreach ($affiliates as $affiliate) {
                    $license = AffiliateLicense::query()->firstOrCreate(
                        ['affiliate_id' => $affiliate->id],
                        [
                            'parent_business_id' => $parent->id,
                            'status' => (string) $affiliate->activation_status === '1' ? 'active' : 'pending',
                            'purchase_amount' => 0,
                            'activated_at' => (string) $affiliate->activation_status === '1' ? now() : null,
                        ],
                    );

                    if ($license->wasRecentlyCreated) {
                        $licensesCreated++;
                    } elseif ($license->parent_business_id !== $parent->id) {
                        throw new RuntimeException("Affiliate {$affiliate->id} already has a licence owned by another parent.");
                    }
                }
            });

        $plansUpdated = 0;
        ProductPlan::query()
            ->where(function ($query) use ($parent, $connection) {
                $query->where(function ($legacy) {
                    $legacy->whereNull('parent_business_id')->whereNull('provider_connection_id');
                })->orWhere(function ($owned) use ($parent, $connection) {
                    $owned->where('parent_business_id', $parent->id)
                        ->where('provider_connection_id', $connection->id);
                });
            })
            ->orderBy('id')->chunkById(250, function ($plans) use ($parent, $connection, &$plansUpdated) {
                foreach ($plans as $plan) {
                    $updates = [];

                    if ($plan->parent_business_id === null) {
                        $updates['parent_business_id'] = $parent->id;
                    }
                    if ($plan->provider_connection_id === null) {
                        $updates['provider_connection_id'] = $connection->id;
                    }
                    if (! $plan->upstream_code) {
                        $updates['upstream_code'] = $this->uniqueUpstreamCode($plan, $connection);
                    }
                    if ($plan->provider_cost === null && is_numeric($plan->cost_price)) {
                        $updates['provider_cost'] = $plan->cost_price;
                    }

                    if ($updates !== []) {
                        $plan->update($updates);
                        $plansUpdated++;
                    }
                }
            });

        $adminsCreated = $migrateAdmins
            ? $this->migrateAdmins($sourceAffiliateId, $parent)
            : 0;

        return [
            'parents_created' => $parent->wasRecentlyCreated ? 1 : 0,
            'connections_created' => $connection->wasRecentlyCreated ? 1 : 0,
            'affiliates_updated' => $affiliatesUpdated,
            'licenses_created' => $licensesCreated,
            'plans_updated' => $plansUpdated,
            'admins_created' => $adminsCreated,
        ];
    }

    private function uniqueUpstreamCode(ProductPlan $plan, ProviderConnection $connection): string
    {
        $preferred = $plan->automation_product_plan_id ?: $plan->api_id ?: "legacy-{$plan->id}";
        $candidate = $preferred;
        $attempt = 0;

        while (ProductPlan::query()
            ->where('provider_connection_id', $connection->id)
            ->where('upstream_code', $candidate)
            ->whereKeyNot($plan->id)
            ->exists()) {
            $attempt++;
            $candidate = "{$preferred}-legacy-{$plan->id}".($attempt > 1 ? "-{$attempt}" : '');
        }

        return $candidate;
    }

    private function assertNoPartialLegacyOwnership($query, string $recordType): void
    {
        $conflict = (clone $query)
            ->where(function ($builder) {
                $builder->where(function ($partial) {
                    $partial->whereNull('parent_business_id')->whereNotNull('provider_connection_id');
                })->orWhere(function ($partial) {
                    $partial->whereNotNull('parent_business_id')->whereNull('provider_connection_id');
                });
            })
            ->first(['id']);

        if ($conflict) {
            throw new RuntimeException("Legacy {$recordType} {$conflict->id} has partial provider ownership.");
        }
    }

    private function migrateAdmins(int $sourceAffiliateId, ParentBusiness $parent): int
    {
        $sourceAffiliate = Affiliate::query()->findOrFail($sourceAffiliateId);
        $isOresamsub = $sourceAffiliate->slug === 'oresamsub'
            || str_contains(strtolower((string) $sourceAffiliate->domain_url), 'oresamsub.com');

        if (! $isOresamsub) {
            throw new InvalidArgumentException('The selected source affiliate is not recognized as OresamSub.');
        }

        $created = 0;

        User::query()
            ->withoutGlobalScopes()
            ->where('affiliate_id', $sourceAffiliate->id)
            ->whereHas('role', fn ($query) => $query->where('role_name', 'Admin'))
            ->orderBy('id')
            ->chunkById(100, function ($admins) use ($parent, &$created) {
                foreach ($admins as $admin) {
                    $existing = ParentAdmin::query()->where('email', $admin->email)->first();

                    if ($existing && $existing->parent_business_id !== $parent->id) {
                        throw new RuntimeException("Parent admin email {$admin->email} belongs to another parent.");
                    }

                    $parentAdmin = $existing;

                    if (! $parentAdmin) {
                        ParentAdmin::query()->getConnection()->table('parent_admins')->insert([
                            'parent_business_id' => $parent->id,
                            'name' => trim("{$admin->first_name} {$admin->last_name}"),
                            'email' => $admin->email,
                            'email_verified_at' => $admin->email_verified_at,
                            'password' => $admin->getRawOriginal('password'),
                            'active' => (string) $admin->active !== '0',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $created++;
                    }
                }
            });

        return $created;
    }
}
