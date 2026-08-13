<?php

namespace App\Services\Reconciliation;

use App\Models\AffiliateSettlementLedgerEntry;
use App\Models\Transaction;
use App\Models\WalletLog;

class TransactionFinancialReconciliationService
{
    /** @return array{balanced: bool, reference: string, issues: array<int, string>} */
    public function audit(Transaction $transaction): array
    {
        $issues = [];

        $providerCost = $this->cents($transaction->provider_cost_snapshot);
        $affiliateCost = $this->cents($transaction->affiliate_cost_snapshot);
        $customerPrice = $this->cents($transaction->customer_price_snapshot);
        $parentProfit = $this->cents($transaction->parent_profit_snapshot);
        $affiliateProfit = $this->cents($transaction->affiliate_profit_snapshot);

        if (in_array(null, [$providerCost, $affiliateCost, $customerPrice, $parentProfit, $affiliateProfit], true)) {
            $issues[] = 'One or more required pricing snapshots are missing.';
        } else {
            if ($providerCost + $parentProfit !== $affiliateCost) {
                $issues[] = 'Parent profit snapshot does not bridge provider cost to affiliate cost.';
            }
            if ($affiliateCost + $affiliateProfit !== $customerPrice) {
                $issues[] = 'Affiliate profit snapshot does not bridge affiliate cost to customer price.';
            }
            if ($this->cents($transaction->amount) !== $customerPrice) {
                $issues[] = 'Transaction amount does not match the customer price snapshot.';
            }
            if ($this->cents($transaction->balance_before) - $this->cents($transaction->balance_after) !== $customerPrice) {
                $issues[] = 'Customer transaction balances do not reflect the snapshotted charge.';
            }
        }

        $debits = $this->walletLogs($transaction, 'PARENT_MANAGED_DATA_DEBIT');
        if ($debits->count() !== 1 || $debits->contains(fn (WalletLog $log) => $this->delta($log) !== -$customerPrice)) {
            $issues[] = 'Customer wallet debit is missing, duplicated or has the wrong amount.';
        }

        $reserve = $this->settlementEntries($transaction, 'reserve');
        $capture = $this->settlementEntries($transaction, 'capture');
        $release = $this->settlementEntries($transaction, 'release');
        if ($reserve->count() !== 1 || $reserve->contains(fn (AffiliateSettlementLedgerEntry $entry) => $this->cents($entry->amount) !== $affiliateCost)) {
            $issues[] = 'Settlement reservation is missing, duplicated or has the wrong amount.';
        }

        $routingStatus = (string) $transaction->routing_status;
        $refunds = $this->walletLogs($transaction, 'PARENT_MANAGED_DATA_REFUND');
        if ($routingStatus === 'successful') {
            if ($capture->count() !== 1 || $capture->contains(fn (AffiliateSettlementLedgerEntry $entry) => $this->cents($entry->amount) !== $affiliateCost) || $release->isNotEmpty()) {
                $issues[] = 'Successful purchase must have one matching capture and no release.';
            }
            if ($refunds->isNotEmpty()) {
                $issues[] = 'Successful purchase must not have a customer refund.';
            }
        } elseif ($routingStatus === 'failed') {
            if ($release->count() !== 1 || $release->contains(fn (AffiliateSettlementLedgerEntry $entry) => $this->cents($entry->amount) !== $affiliateCost) || $capture->isNotEmpty()) {
                $issues[] = 'Failed purchase must have one matching release and no capture.';
            }
            if ($refunds->count() !== 1 || $refunds->contains(fn (WalletLog $log) => $this->delta($log) !== $customerPrice)) {
                $issues[] = 'Failed purchase customer refund is missing, duplicated or has the wrong amount.';
            }
        } elseif ($capture->isNotEmpty() || $release->isNotEmpty() || $refunds->isNotEmpty()) {
            $issues[] = 'Unresolved purchase has an unexpected capture, release or customer refund.';
        }

        return [
            'balanced' => $issues === [],
            'reference' => (string) $transaction->txn_reference,
            'issues' => $issues,
        ];
    }

    private function walletLogs(Transaction $transaction, string $category)
    {
        return WalletLog::withoutGlobalScope('affiliate')
            ->where('transaction_id', $transaction->id)
            ->where('transaction_category', $category)
            ->get();
    }

    private function settlementEntries(Transaction $transaction, string $action)
    {
        return AffiliateSettlementLedgerEntry::query()
            ->where('parent_business_id', $transaction->parent_business_id)
            ->where('affiliate_id', $transaction->affiliate_id)
            ->where('reference', $this->actionReference((string) $transaction->txn_reference, $action))
            ->get();
    }

    private function actionReference(string $purchaseReference, string $action): string
    {
        $reference = "{$purchaseReference}:{$action}";

        return strlen($reference) <= 191 ? $reference : substr($purchaseReference, 0, 100).':'.hash('sha256', $reference).":{$action}";
    }

    private function delta(WalletLog $log): ?int
    {
        $before = $this->cents($log->balance_before);
        $after = $this->cents($log->balance_after);

        return $before === null || $after === null ? null : $after - $before;
    }

    private function cents(mixed $amount): ?int
    {
        if ($amount === null || ! preg_match('/^-?\d+(?:\.\d{1,2})?$/', trim((string) $amount))) {
            return null;
        }

        $negative = str_starts_with((string) $amount, '-');
        [$whole, $fraction] = array_pad(explode('.', ltrim((string) $amount, '-'), 2), 2, '');
        $cents = ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');

        return $negative ? -$cents : $cents;
    }
}
