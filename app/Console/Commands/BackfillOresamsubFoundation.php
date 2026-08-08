<?php

namespace App\Console\Commands;

use App\Services\MultiParent\OresamsubFoundationBackfillService;
use Illuminate\Console\Command;
use Throwable;

class BackfillOresamsubFoundation extends Command
{
    protected $signature = 'multi-parent:backfill-oresamsub-foundation
        {--dry-run : Execute and roll back all writes}
        {--commit : Persist the backfill}';

    protected $description = 'Backfill OresamSub parent ownership, pricing, routes, and transaction snapshots';

    public function handle(OresamsubFoundationBackfillService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $commit = (bool) $this->option('commit');

        if ($dryRun === $commit) {
            $this->error('Specify exactly one of --dry-run or --commit.');

            return self::FAILURE;
        }

        try {
            $counts = $service->run($dryRun);
            $this->table(['Entity', 'Count'], collect($counts)->map(fn ($count, $entity) => [$entity, $count])->values()->all());

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception::class.': '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
