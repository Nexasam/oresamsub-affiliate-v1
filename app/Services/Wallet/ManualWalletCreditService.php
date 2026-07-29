<?php

namespace App\Services\Wallet;

use App\Models\Admin;
use App\Models\User;
use App\Models\WalletLog;
use Illuminate\Support\Facades\DB;

class ManualWalletCreditService
{
    public function credit(User $user, float $amount, Admin $admin, string $reason): User
    {
        return DB::transaction(function () use ($user, $amount, $admin, $reason) {
            $user = User::withoutGlobalScope('affiliate')->lockForUpdate()->findOrFail($user->id);
            $before = (float) $user->main_wallet;
            $after = $before + $amount;
            $user->forceFill(['main_wallet' => $after])->save();

            WalletLog::withoutGlobalScope('affiliate')->create([
                'affiliate_id' => $user->affiliate_id,
                'user_id' => $user->id,
                'transaction_id' => null,
                'action_by' => 'platform_admin:'.$admin->id,
                'transaction_category' => 'PLATFORM_ADMIN_WALLET_CREDITING',
                'balance_before' => $before,
                'balance_after' => $after,
                'description' => $reason,
            ]);

            return $user->fresh();
        });
    }
}
