<?php

namespace App\Console\Commands;

use App\Models\Affiliate;
use App\Services\AffiliateProductPlanSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncAffiliateProductPlans extends Command
{
    protected $signature = 'affiliate-catalogs:sync {--affiliate= : Sync one affiliate ID only}';
    protected $description = 'Synchronize parent product plans into affiliate catalogues';

    public function handle(AffiliateProductPlanSyncService $sync): int
    {
        $query = Affiliate::query()->whereNotNull('parent_business_id');
        if ($affiliateId = $this->option('affiliate')) {
            $query->whereKey($affiliateId);
        }

        $failed = 0;
        $query->orderBy('id')->chunkById(100, function ($affiliates) use ($sync, &$failed): void {
            foreach ($affiliates as $affiliate) {
                try {
                    $counts = $sync->sync($affiliate);
                    $this->line("{$affiliate->slug}: {$counts['created']} created, {$counts['updated']} updated");
                } catch (Throwable $exception) {
                    report($exception);
                    $failed++;
                    $this->error("{$affiliate->slug}: {$exception->getMessage()}");
                }
            }
        });

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
