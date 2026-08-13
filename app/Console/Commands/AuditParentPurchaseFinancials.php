<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\Reconciliation\TransactionFinancialReconciliationService;
use Illuminate\Console\Command;

class AuditParentPurchaseFinancials extends Command
{
    protected $signature = 'parent-purchases:audit-financials
        {--parent= : Limit the audit to one parent business ID}
        {--reference= : Audit one transaction reference}
        {--limit=500 : Maximum transactions to inspect}';

    protected $description = 'Read-only audit of parent-managed pricing, customer wallet and settlement ledger movements';

    public function handle(TransactionFinancialReconciliationService $reconciliation): int
    {
        $query = Transaction::withoutGlobalScope('affiliate')
            ->whereNotNull('parent_business_id')
            ->whereNotNull('affiliate_cost_snapshot')
            ->when($this->option('parent'), fn ($query, $parent) => $query->where('parent_business_id', (int) $parent))
            ->when($this->option('reference'), fn ($query, $reference) => $query->where('txn_reference', trim((string) $reference)))
            ->latest('id')
            ->limit(max(1, min(5000, (int) $this->option('limit'))));

        $transactions = $query->get();
        $mismatches = [];
        foreach ($transactions as $transaction) {
            $audit = $reconciliation->audit($transaction);
            if (! $audit['balanced']) {
                $mismatches[] = [
                    $transaction->id,
                    $audit['reference'],
                    $transaction->routing_status ?: 'unknown',
                    implode(' ', $audit['issues']),
                ];
            }
        }

        if ($mismatches !== []) {
            $this->table(['ID', 'Reference', 'Routing', 'Issue(s)'], $mismatches);
        }

        $balanced = $transactions->count() - count($mismatches);
        $this->components->info("Audited {$transactions->count()} parent-managed purchase(s): {$balanced} balanced, ".count($mismatches).' mismatch(es).');

        return $mismatches === [] ? self::SUCCESS : self::FAILURE;
    }
}
