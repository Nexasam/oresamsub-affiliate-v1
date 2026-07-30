<?php

namespace App\Services\Transaction;

use App\Models\Admin;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlatformAdminTransactionStatusService
{
    public function update(
        Transaction $transaction,
        int $targetStatus,
        bool $impactWallet,
        string $reason,
        Admin $admin
    ): array {
        return DB::transaction(function () use ($transaction, $targetStatus, $impactWallet, $reason, $admin) {
            $transaction = Transaction::withoutGlobalScope('affiliate')->lockForUpdate()->findOrFail($transaction->id);
            $oldStatus = (int) $transaction->status;
            $walletChange = 0.0;
            $walletMessage = 'Status changed without a wallet adjustment.';

            if ($impactWallet) {
                if ($transaction->wallet_category !== 'main_wallet') {
                    throw ValidationException::withMessages([
                        'impact_wallet' => 'Automatic wallet impact currently supports main-wallet transactions only.',
                    ]);
                }

                $user = User::withoutGlobalScope('affiliate')->lockForUpdate()->findOrFail($transaction->user_id);
                $amount = is_numeric($transaction->discounted_amount)
                    ? (float) $transaction->discounted_amount
                    : (float) $transaction->amount;

                $credits = (float) WalletLog::withoutGlobalScope('affiliate')
                    ->where('transaction_id', $transaction->id)
                    ->where('transaction_category', 'PLATFORM_ADMIN_TRANSACTION_CREDIT')
                    ->sum(DB::raw('CAST(balance_after AS DECIMAL(18,2)) - CAST(balance_before AS DECIMAL(18,2))'));
                $debits = abs((float) WalletLog::withoutGlobalScope('affiliate')
                    ->where('transaction_id', $transaction->id)
                    ->where('transaction_category', 'PLATFORM_ADMIN_TRANSACTION_DEBIT')
                    ->sum(DB::raw('CAST(balance_after AS DECIMAL(18,2)) - CAST(balance_before AS DECIMAL(18,2))')));
                $outstandingCredit = round($credits - $debits, 2);

                if (in_array($targetStatus, [-1, 2], true) && $oldStatus !== 2 && $outstandingCredit <= 0) {
                    $walletChange = $amount;
                    $this->adjustWallet($transaction, $user, $walletChange, $admin, $reason);
                    $walletMessage = 'The deducted transaction amount was credited to the customer wallet.';
                } elseif ($targetStatus === 1 && ($outstandingCredit > 0 || $oldStatus === 2)) {
                    $walletChange = -($outstandingCredit > 0 ? min($amount, $outstandingCredit) : $amount);
                    if ((float) $user->main_wallet < abs($walletChange)) {
                        throw ValidationException::withMessages([
                            'impact_wallet' => 'The customer wallet does not have enough balance to reverse the earlier credit.',
                        ]);
                    }
                    $this->adjustWallet($transaction, $user, $walletChange, $admin, $reason);
                    $walletMessage = 'The earlier platform-admin refund/failure credit was reversed.';
                } else {
                    $walletMessage = $targetStatus === 1
                        ? 'No earlier platform-admin credit exists, so success did not debit the wallet again.'
                        : 'Wallet impact was already applied for this transaction; no duplicate credit was made.';
                }
            }

            $transaction->forceFill([
                'status' => $targetStatus,
                'set_for_manual' => 0,
                'manually_processed_by' => 'platform_admin:'.$admin->id,
                'refund_reason' => $targetStatus === 2 ? $reason : $transaction->refund_reason,
                'admin_screen_message' => trim(($transaction->admin_screen_message ? $transaction->admin_screen_message."\n" : '')
                    ."[Platform admin status: {$oldStatus} → {$targetStatus}] {$reason}"),
            ])->save();

            return [
                'transaction' => $transaction->fresh()->load(['affiliate:id,name', 'user:id,first_name,last_name,email']),
                'old_status' => $oldStatus,
                'wallet_change' => $walletChange,
                'wallet_message' => $walletMessage,
            ];
        });
    }

    private function adjustWallet(
        Transaction $transaction,
        User $user,
        float $change,
        Admin $admin,
        string $reason
    ): void {
        $before = (float) $user->main_wallet;
        $after = $before + $change;
        $user->forceFill(['main_wallet' => $after])->save();

        WalletLog::withoutGlobalScope('affiliate')->create([
            'affiliate_id' => $transaction->affiliate_id,
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'action_by' => 'platform_admin:'.$admin->id,
            'transaction_category' => $change > 0
                ? 'PLATFORM_ADMIN_TRANSACTION_CREDIT'
                : 'PLATFORM_ADMIN_TRANSACTION_DEBIT',
            'balance_before' => $before,
            'balance_after' => $after,
            'description' => $reason,
        ]);
    }
}
