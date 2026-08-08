<?php

namespace App\Console\Commands;

use App\Services\MultiParent\OresamsubBackfillService;
use Illuminate\Console\Command;
use Throwable;

class BackfillOresamsubParent extends Command
{
    protected $signature = 'multi-parent:backfill-oresamsub
        {--source-affiliate= : Existing OresamSub affiliate ID whose admins may be copied}
        {--migrate-admins : Copy eligible OresamSub admins into parent_admins}
        {--dry-run : Report changes and roll them back}
        {--commit : Persist the backfill changes}';

    protected $description = 'Backfill existing OresamSub data into the multi-parent schema';

    public function handle(OresamsubBackfillService $service): int
    {
        if ($this->option('migrate-admins') && ! $this->option('source-affiliate')) {
            $this->error('--source-affiliate is required when --migrate-admins is used.');

            return self::FAILURE;
        }

        if ($this->option('dry-run') === $this->option('commit')) {
            $this->error('Choose exactly one of --dry-run or --commit.');

            return self::FAILURE;
        }

        try {
            $result = $service->run(
                sourceAffiliateId: $this->option('source-affiliate')
                    ? (int) $this->option('source-affiliate')
                    : null,
                migrateAdmins: (bool) $this->option('migrate-admins'),
                dryRun: (bool) $this->option('dry-run'),
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($this->option('dry-run') ? 'DRY RUN — no changes were saved.' : 'Backfill committed.');
        $this->table(['Metric', 'Count'], collect($result)->map(
            fn (int $count, string $metric) => [$metric, $count]
        )->values()->all());

        return self::SUCCESS;
    }
}
