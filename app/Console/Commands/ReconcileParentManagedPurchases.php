<?php

namespace App\Console\Commands;

use App\Models\Affiliate;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Providers\ParentManagedPurchaseOrchestrator;
use App\Services\Providers\ParentPurchaseExecutor;
use Illuminate\Console\Command;
use Throwable;

class ReconcileParentManagedPurchases extends Command
{
    protected $signature = 'parent-purchases:reconcile {--limit=100}';
    protected $description = 'Safely requery uncertain parent-managed provider purchases';

    public function handle(ParentPurchaseExecutor $executor, ParentManagedPurchaseOrchestrator $orchestrator): int
    {
        if (! config('parent_businesses.features.parent_managed_purchases')) {
            $this->components->info('Parent-managed purchases are disabled.');
            return self::SUCCESS;
        }

        Transaction::withoutGlobalScope('affiliate')
            ->where('routing_status', 'reconciliation_required')
            ->where('retry_count', '>=', 10)
            ->update([
                'routing_status' => 'reconciliation_exhausted',
                'admin_screen_message' => 'Automatic requery limit reached; manual review is required.',
            ]);

        $transactions = Transaction::withoutGlobalScope('affiliate')
            ->where('routing_status', 'reconciliation_required')
            ->where('retry_count', '<', 10)
            ->where(function ($query) {
                $query->where('retry_count', 0)
                    ->orWhere(function ($retry) {
                        $retry->where('retry_count', '>', 0)->where('updated_at', '<=', now()->subMinutes(5));
                    });
            })
            ->orderBy('id')->limit(max(1, min(500, (int) $this->option('limit'))))->get();

        $resolved = 0;
        $manualReview = 0;
        foreach ($transactions as $transaction) {
            $affiliate = Affiliate::query()->find($transaction->affiliate_id);
            $customer = User::withoutGlobalScope('affiliate')->find($transaction->user_id);
            if (! $affiliate || ! $customer) {
                $transaction->forceFill(['routing_status' => 'reconciliation_exhausted', 'admin_screen_message' => 'Affiliate or customer record is unavailable.'])->save();
                $manualReview++;
                continue;
            }

            try {
                $result = $executor->requery($transaction);
                $transaction->increment('retry_count');
                $updated = $orchestrator->finalize($transaction->fresh(), $affiliate, $customer, $result);
            } catch (Throwable $exception) {
                report($exception);
                $transaction->increment('retry_count');
                $updated = $transaction->fresh();
                $updated->forceFill(['admin_screen_message' => 'Automatic requery failed: '.$exception->getMessage()])->save();
            }

            if ($updated->routing_status === 'reconciliation_required' && $updated->retry_count >= 10) {
                $updated->forceFill(['routing_status' => 'reconciliation_exhausted', 'admin_screen_message' => 'Automatic requery limit reached; manual review is required.'])->save();
            }
            if ($updated->fresh()->routing_status === 'reconciliation_exhausted') {
                $manualReview++;
            } elseif ($updated->routing_status !== 'reconciliation_required') {
                $resolved++;
            }
        }

        $this->components->info("Processed {$transactions->count()} uncertain purchase(s): {$resolved} resolved, {$manualReview} moved to manual review.");
        return self::SUCCESS;
    }
}
