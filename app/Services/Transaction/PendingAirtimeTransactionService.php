<?php

namespace App\Services\Transactions;

use App\Models\Affiliate;
use App\Models\Transaction;

class PendingAirtimeTransactionService
{
    public function process(): int
    {
        $pendingTransactions = Transaction::withoutGlobalScope('affiliate')
            ->where('admin_screen_message', 'pending_airtime_transaction')
            ->whereNotNull('txn_reference')
            ->where('transaction_category', 'airtime')
            ->where('status', 0)
            ->get();

        foreach ($pendingTransactions as $transaction) {

            try {

                $this->processSingleTransaction($transaction);

                sleep(1);

            } catch (\Throwable $e) {

                logger()->error('Pending airtime processing failed', [
                    'transaction_id' => $transaction->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        logger(count($pendingTransactions) . ' Pending airtime transactions processed');

        return count($pendingTransactions);
    }

    protected function processSingleTransaction(Transaction $transaction): void
    {
        $affiliate = Affiliate::select('parent_key')
            ->find($transaction->affiliate_id);

        if (!$affiliate || !$affiliate->parent_key) {
            return;
        }

        $response = $this->fetchParentTransaction(
            $transaction->txn_reference,
            $affiliate->parent_key
        );

        logger('Parent txn response: ' . json_encode($response));

        if (
            isset($response['data']['status']) &&
            $response['data']['status'] == 1
        ) {

            $transaction->update([
                'status' => 1,
                'user_screen_message' =>
                    $response['data']['user_screen_message']
                    ?? 'Successfully processed',
                'admin_screen_message' =>
                    $response['data']['admin_screen_message']
                    ?? 'Successfully processed',
                'set_for_manual' => 0,
            ]);

            return;
        }

        if (
            isset($response['data']['status']) &&
            in_array($response['data']['status'], [2, -1])
        ) {

            $this->refundTransaction(
                $transaction,
                $response
            );
        }
    }

    protected function fetchParentTransaction(
        string $reference,
        string $apiKey
    ): array {

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://oresamsub.com/api/v1/user/fetch_transaction?reference={$reference}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                "Authorization: {$apiKey}",
                'Content-Type: application/json',
                'Accept: application/json'
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response, true) ?? [];
    }

    protected function refundTransaction(
        Transaction $transaction,
        array $response
    ): void {

        if ($transaction->wallet_category !== 'main_wallet') {

            logger('data_wallet not available at the moment');

            return;
        }

        $user = $transaction->user;

        $refundAmount =
            $transaction->discounted_amount
            ?? $transaction->amount;

        $oldBalance = $user->main_wallet;
        $newBalance = $oldBalance + $refundAmount;

        $user->update([
            'main_wallet' => $newBalance
        ]);

        $transaction->update([
            'status' => $response['data']['status'],
            'set_for_manual' => 0,
            'balance_after' => $transaction->balance_before,
            'user_screen_message' =>
                $response['data']['user_screen_message']
                ?? 'Transaction failed',
            'admin_screen_message' =>
                $response['data']['admin_screen_message']
                ?? 'Transaction failed',
        ]);

        $walletLog = [
            'user_id' => $user->id,
            'transaction_category' => 'REFUND_TRANSACTION',
            'balance_before' => $oldBalance,
            'balance_after' => $newBalance,
            'transaction_id' => $transaction->id,
            'action_by' => $user->id,
            'description' => 'Transaction refunded. ID: ' . $transaction->id,
        ];

        app(\App\Services\Utils\UtilService::class)
            ->log_wallet_transactions($walletLog);
    }
}