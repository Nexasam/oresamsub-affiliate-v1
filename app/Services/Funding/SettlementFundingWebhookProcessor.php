<?php

namespace App\Services\Funding;

use App\Models\AffiliateSettlementVirtualAccount;
use App\Models\FundingWebhookEvent;
use App\Models\ParentFundingProvider;
use App\Services\Wallet\AffiliateSettlementWalletService;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class SettlementFundingWebhookProcessor
{
    public function __construct(
        private readonly FundingChargeCalculator $charges,
        private readonly AffiliateSettlementWalletService $wallets,
    ) {}

    public function process(FundingWebhookEvent $event, ParentFundingProvider $provider, array $data): string
    {
        if (! $data['successful'] || ! is_numeric($data['gross_amount'])) return $this->finish($event, 'ignored');
        if (! filled($data['account_number'] ?? null) && ! filled($data['account_reference'] ?? null)) {
            return $this->finish($event, 'unresolved');
        }

        $account = AffiliateSettlementVirtualAccount::query()
            ->where('parent_funding_provider_id', $provider->id)->where('status', 'active')
            ->where(function ($query) use ($data) {
                if (filled($data['account_number'] ?? null)) $query->orWhere('account_number', $data['account_number']);
                if (filled($data['account_reference'] ?? null)) $query->orWhere('account_reference', $data['account_reference']);
            })->first();
        if (! $account) return $this->finish($event, 'unresolved');

        return DB::transaction(function () use ($event, $provider, $data, $account) {
            $provider->loadMissing('banks');
            $bank = $provider->banks->first(fn ($bank) => $bank->active && (
                strcasecmp((string) $bank->bank_code, (string) $data['bank_name']) === 0 ||
                strcasecmp((string) $bank->name, (string) $data['bank_name']) === 0
            ));
            $gross = BigDecimal::of((string) $data['gross_amount'])->toScale(2);
            $charge = $bank
                ? BigDecimal::of($this->charges->calculate($bank->rate_type, (string) $bank->rate_value, $bank->percentage_cap !== null ? (string) $bank->percentage_cap : null, (string) $gross))
                : $gross->minus(BigDecimal::of((string) ($data['settlement_amount'] ?? $data['gross_amount'])));
            $net = $gross->minus($charge)->toScale(2);
            if ($net->isLessThanOrEqualTo('0')) return $this->finish($event, 'unresolved');

            $event->update(['parent_business_id' => $account->parent_business_id, 'affiliate_id' => $account->affiliate_id]);
            $this->wallets->creditFromWebhook($account->affiliate, (string) $net, 'FUNDING:'.$event->external_event_id, [
                'funding_provider_id' => $event->funding_provider_id, 'parent_funding_provider_id' => $provider->id,
                'settlement_virtual_account_id' => $account->id, 'gross_amount' => (string) $gross, 'charge' => (string) $charge,
                'external_event_id' => $event->external_event_id,
            ]);
            return $this->finish($event, 'processed');
        });
    }

    private function finish(FundingWebhookEvent $event, string $status): string
    {
        $event->update(['status' => $status, 'processed_at' => now()]);
        return $status;
    }
}
