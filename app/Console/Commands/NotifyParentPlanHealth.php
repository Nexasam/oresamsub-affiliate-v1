<?php

namespace App\Console\Commands;

use App\Models\ParentBusiness;
use App\Services\ParentAdmin\PlanHealthAlertService;
use App\Services\ParentAdmin\PlanHealthNotificationService;
use Illuminate\Console\Command;

class NotifyParentPlanHealth extends Command
{
    protected $signature = 'parent-plans:notify-health {--limit=10}';
    protected $description = 'Create deduplicated parent-admin notifications for unhealthy provider routes';

    public function handle(PlanHealthAlertService $alerts, PlanHealthNotificationService $notifications): int
    {
        ParentBusiness::query()->orderBy('id')->chunkById(100, function ($parents) use ($alerts, $notifications): void {
            foreach ($parents as $parent) {
                $notifications->sync($parent, $alerts->forParent($parent, (int) $this->option('limit')));
            }
        });

        return self::SUCCESS;
    }
}

