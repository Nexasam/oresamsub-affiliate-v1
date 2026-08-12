<?php

namespace App\Services\MultiParent;

use App\Models\Affiliate;
use App\Models\AffiliateProcessingProfile;
use App\Models\ParentBusiness;
use App\Services\PlatformAdmin\ParentBusinessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResetAffiliateTenantService
{
    public function __construct(private readonly ParentBusinessService $parents) {}

    /** @return array<string, int> */
    public function inventory(Affiliate $affiliate): array
    {
        $counts = [];
        foreach ($this->affiliateTables() as $table) {
            $count = DB::table($table)->where('affiliate_id', $affiliate->id)->count();
            if ($count > 0) {
                $counts[$table] = $count;
            }
        }
        $counts['affiliates'] = 1;
        ksort($counts);

        return $counts;
    }

    public function reset(Affiliate $affiliate, array $input): Affiliate
    {
        if ($affiliate->parentBusiness?->slug !== 'oresamsub') {
            throw ValidationException::withMessages(['domain' => 'Only an OresamSub legacy affiliate can be reset by this command.']);
        }

        return DB::transaction(function () use ($affiliate, $input) {
            $oldId = $affiliate->id;
            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }

            try {
                foreach ($this->deletionOrder() as $table) {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, 'affiliate_id')) {
                        DB::table($table)->where('affiliate_id', $oldId)->delete();
                    }
                }
                foreach ($this->affiliateTables() as $table) {
                    DB::table($table)->where('affiliate_id', $oldId)->delete();
                }
                DB::table('affiliates')->where('id', $oldId)->delete();
            } finally {
                if ($driver === 'mysql') {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');
                }
            }

            $parent = $this->parents->create([
                'business' => [
                    'name' => $input['parent_name'], 'slug' => $input['parent_slug'],
                    'contact_email' => $input['parent_admin_email'], 'contact_phone' => null, 'status' => 'active',
                ],
                'admin' => [
                    'name' => $input['parent_admin_name'], 'email' => $input['parent_admin_email'],
                    'password' => $input['password'], 'active' => true, 'must_change_password' => true,
                ],
            ]);
            $level = $parent->resellerLevels()->where('position', 1)->firstOrFail();
            $fresh = Affiliate::create([
                'parent_business_id' => $parent->id, 'parent_reseller_level_id' => $level->id,
                'name' => $input['affiliate_name'], 'slug' => $input['affiliate_slug'],
                'affiliate_plan_id' => 1, 'ip_address' => 'managed-'.Str::uuid(),
                'domain_url' => $input['domain'], 'contact_phone' => 'managed-'.Str::uuid(),
                'contact_email' => $input['parent_admin_email'], 'parent_key' => Str::random(48),
                'parent_email' => 'parent+'.$input['affiliate_slug'].'@affiliate.local', 'activation_status' => 1,
            ]);
            AffiliateProcessingProfile::create([
                'parent_business_id' => $parent->id, 'affiliate_id' => $fresh->id,
                'management_mode' => 'parent_managed', 'processing_engine' => 'multi_parent', 'status' => 'active',
            ]);

            return $fresh->fresh(['parentBusiness', 'processingProfile']);
        });
    }

    /** @return list<string> */
    private function affiliateTables(): array
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $tables = collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))->pluck('name');
        } else {
            $tables = collect(DB::select('SELECT TABLE_NAME AS name FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = ?', ['affiliate_id']))->pluck('name');
        }

        return $tables->filter(fn ($table) => $table !== 'affiliates' && Schema::hasColumn($table, 'affiliate_id'))->unique()->values()->all();
    }

    /** @return list<string> */
    private function deletionOrder(): array
    {
        return [
            'affiliate_processing_change_requests', 'affiliate_settlement_ledger_entries',
            'affiliate_funding_provider_banks', 'funding_mode_change_requests', 'funding_webhook_events',
            'affiliate_funding_provider_configs', 'affiliate_settlement_wallets', 'affiliate_processing_profiles',
            'wallet_logs', 'commissions', 'transactions', 'used_user_coupon_codes',
            'user_virtual_accounts', 'user_monnify_virtual_accounts', 'user_wallet_funding_promos',
            'used_wallet_funding_promos', 'user_bulk_data_purchases', 'user_bulk_data_wallets',
            'users', 'affiliate_product_plans', 'affiliate_product_plan_categories', 'affiliate_user_plans',
            'affiliate_onboarding_requests', 'platform_impersonation_tokens',
        ];
    }
}
