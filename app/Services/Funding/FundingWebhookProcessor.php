<?php

namespace App\Services\Funding;

use App\Support\BrickMathRounding;
use App\Models\AffiliateFundingProviderConfig;
use App\Models\FundingWebhookEvent;
use App\Models\MaxCrystalPaymentsPendingApproval;
use App\Models\Setting;
use App\Models\User;
use App\Models\WalletLog;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class FundingWebhookProcessor
{
    public function __construct(private readonly FundingChargeCalculator $charges) {}

    public function process(FundingWebhookEvent $event, AffiliateFundingProviderConfig $config, array $data): string
    {
        if (! $data['successful']) {
            $event->update(['status' => 'ignored', 'processed_at' => now()]);

            return 'ignored';
        }

        if (! filled($data['email']) || ! is_numeric($data['gross_amount'])) {
            $event->update(['status' => 'unresolved', 'processed_at' => now()]);

            return 'unresolved';
        }

        return DB::transaction(function () use ($event, $config, $data) {
            $user = User::withoutGlobalScope('affiliate')->where('affiliate_id', $config->affiliate_id)
                ->where('email', $data['email'])->lockForUpdate()->first();
            if (! $user) {
                $event->update(['status' => 'unresolved', 'processed_at' => now()]);

                return 'unresolved';
            }

            $config->loadMissing(['banks.parentBank', 'parentFundingProvider.banks', 'parentFundingProvider.fundingProvider']);
            $bank = $this->effectiveBank($config, (string) $data['bank_name']);
            $gross = BigDecimal::of((string) $data['gross_amount'])->toScale(2, BrickMathRounding::halfUp());
            $charge = $bank
                ? BigDecimal::of($this->charges->calculate($bank->rate_type, (string) $bank->rate_value, $bank->percentage_cap !== null ? (string) $bank->percentage_cap : null, (string) $gross))
                : $gross->minus(BigDecimal::of((string) ($data['settlement_amount'] ?? $data['gross_amount'])));
            $net = $gross->minus($charge)->toScale(2, BrickMathRounding::halfUp());
            if ($net->isNegative()) {
                $event->update(['status' => 'unresolved', 'processed_at' => now()]);

                return 'unresolved';
            }

            $automaticLimit = Setting::withoutGlobalScope('affiliate')
                ->where('affiliate_id', $config->affiliate_id)
                ->where('field_name', 'max_automatic_crediting_allowed')
                ->value('field_value');
            if (is_numeric($automaticLimit) && $net->isGreaterThan((string) $automaticLimit)) {
                MaxCrystalPaymentsPendingApproval::withoutGlobalScope('affiliate')->updateOrCreate([
                    'affiliate_id' => $config->affiliate_id,
                    'payment_reference' => $event->external_event_id,
                ], [
                    'user_id' => $user->id,
                    'amount' => (string) $net,
                    'status' => 0,
                ]);
                $event->update(['status' => 'pending_review', 'processed_at' => now()]);

                return 'pending_review';
            }

            $before = BigDecimal::of((string) ($user->main_wallet ?? 0))->toScale(2, BrickMathRounding::halfUp());
            $after = $before->plus($net)->toScale(2, BrickMathRounding::halfUp());
            $user->update(['main_wallet' => (string) $after]);
            WalletLog::withoutGlobalScope('affiliate')->create([
                'affiliate_id' => $config->affiliate_id,
                'user_id' => $user->id,
                'transaction_id' => $event->external_event_id,
                'action_by' => 'webhook',
                'transaction_category' => strtoupper($config->parentFundingProvider->fundingProvider->adapter_key).'_WALLET_FUNDING',
                'balance_before' => (string) $before,
                'balance_after' => (string) $after,
                'description' => "Wallet credited with {$net} after a {$charge} funding charge.",
            ]);
            $event->update(['status' => 'processed', 'processed_at' => now()]);

            return 'processed';
        });
    }

    private function effectiveBank(AffiliateFundingProviderConfig $config, string $bankName): mixed
    {
        $matches = fn ($bank) => strcasecmp((string) $bank->name, $bankName) === 0
            || strcasecmp((string) $bank->bank_code, $bankName) === 0;

        if ($config->management_mode === 'affiliate_managed') {
            return $config->banks->first(fn ($setting) => $setting->active && $setting->parentBank?->active && $matches($setting->parentBank));
        }

        return $config->parentFundingProvider->banks->first(fn ($bank) => $bank->active && $matches($bank));
    }
}
