<?php

namespace App\Services\Providers;

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletLog;
use App\Services\Pricing\MultiParentPricingResolver;
use App\Services\Wallet\AffiliateSettlementWalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ParentManagedPurchaseOrchestrator
{
    public function __construct(
        private readonly MultiParentPricingResolver $pricing,
        private readonly ParentPurchaseExecutor $executor,
        private readonly AffiliateSettlementWalletService $settlements,
    ) {}

    /** @return array{transaction: Transaction, provider_result: array<string, mixed>|null} */
    public function purchase(User $customer, AffiliateProductPlan $affiliatePlan, array $runtime, int $customerLevel, ?string $faceAmount = null): array
    {
        $reference = trim((string) ($runtime['reference'] ?? ''));
        if ($reference === '') {
            throw ValidationException::withMessages(['reference' => 'A unique purchase reference is required.']);
        }

        $existing = Transaction::withoutGlobalScope('affiliate')->where('txn_reference', $reference)->first();
        if ($existing) {
            return ['transaction' => $existing, 'provider_result' => null];
        }

        $affiliate = Affiliate::query()->findOrFail($affiliatePlan->affiliate_id);
        $profile = $affiliate->processingProfile;
        if (! $profile || $profile->status !== 'active' || $profile->processing_engine !== 'multi_parent' || $profile->management_mode !== 'parent_managed') {
            throw ValidationException::withMessages(['processing_profile' => 'This affiliate is not enabled for parent-managed processing.']);
        }
        if ((int) $customer->affiliate_id !== (int) $affiliate->id) {
            throw ValidationException::withMessages(['customer' => 'The customer does not belong to this affiliate.']);
        }

        $price = $this->pricing->resolve($affiliate, $affiliatePlan, $customerLevel, $faceAmount);
        $transaction = $this->prepare($customer, $affiliate, $affiliatePlan, $runtime, $reference, $price);

        try {
            $result = $this->executor->execute($affiliatePlan, $runtime);
        } catch (Throwable $exception) {
            report($exception);
            $result = [
                'status' => 0, 'successful' => false, 'ambiguous' => true,
                'requires_reconciliation' => true, 'refundable' => false,
                'user_message' => 'The provider response is uncertain and requires reconciliation.',
                'admin_message' => $exception->getMessage(), 'routing_status' => 'reconciliation_required',
            ];
        }

        return ['transaction' => $this->finalize($transaction, $affiliate, $customer, $result), 'provider_result' => $result];
    }

    private function prepare(User $customer, Affiliate $affiliate, AffiliateProductPlan $affiliatePlan, array $runtime, string $reference, array $price): Transaction
    {
        return DB::transaction(function () use ($customer, $affiliate, $affiliatePlan, $runtime, $reference, $price) {
            $lockedCustomer = User::withoutGlobalScope('affiliate')->lockForUpdate()->findOrFail($customer->id);
            $before = $this->cents((string) $lockedCustomer->main_wallet);
            $charge = $this->cents($price['customer_selling_price']);
            if ($before < $charge) {
                throw ValidationException::withMessages(['wallet' => 'Insufficient customer wallet balance.']);
            }

            $this->settlements->reserve($affiliate, $price['affiliate_acquisition_price'], $reference, 'user', $lockedCustomer->id);
            $after = $before - $charge;
            $lockedCustomer->forceFill(['main_wallet' => $this->money($after)])->save();

            $plan = $affiliatePlan->product_plan;
            $transaction = Transaction::withoutGlobalScope('affiliate')->create([
                'affiliate_id' => $affiliate->id,
                'parent_business_id' => $affiliate->parent_business_id,
                'api_id' => (string) ($plan->api_id ?? $plan->id),
                'txn_reference' => $reference,
                'affiliate_product_plan_id' => $affiliatePlan->id,
                'user_id' => $lockedCustomer->id,
                'transaction_category' => 'data',
                'wallet_category' => 'main_wallet',
                'phone_number' => $runtime['phone_number'] ?? $runtime['mobile_number'] ?? null,
                'amount' => $price['customer_selling_price'],
                'balance_before' => $this->money($before),
                'balance_after' => $this->money($after),
                'description' => 'Parent-managed data purchase',
                'status' => 0,
                'routing_status' => 'processing',
                'provider_cost_snapshot' => $price['provider_cost'],
                'parent_cost_snapshot' => $price['provider_cost'],
                'affiliate_cost_snapshot' => $price['affiliate_acquisition_price'],
                'customer_price_snapshot' => $price['customer_selling_price'],
                'parent_profit_snapshot' => $price['parent_profit'],
                'affiliate_profit_snapshot' => $price['affiliate_profit'],
            ]);

            WalletLog::withoutGlobalScope('affiliate')->create([
                'affiliate_id' => $affiliate->id, 'user_id' => $lockedCustomer->id,
                'transaction_id' => $transaction->id, 'action_by' => $lockedCustomer->id,
                'transaction_category' => 'PARENT_MANAGED_DATA_DEBIT',
                'balance_before' => $this->money($before), 'balance_after' => $this->money($after),
                'description' => "Data purchase {$reference}",
            ]);

            return $transaction;
        });
    }

    public function finalize(Transaction $transaction, Affiliate $affiliate, User $customer, array $result): Transaction
    {
        return DB::transaction(function () use ($transaction, $affiliate, $customer, $result) {
            $locked = Transaction::withoutGlobalScope('affiliate')->lockForUpdate()->findOrFail($transaction->id);
            if (! in_array($locked->routing_status, ['processing', 'reconciliation_required'], true)) {
                return $locked;
            }

            $routing = (string) ($result['routing_status'] ?? 'reconciliation_required');
            $updates = [
                'status' => $result['status'] ?? 0,
                'routing_status' => $routing,
                'provider_reference' => $result['provider_reference'] ?? null,
                'parent_provider_connection_id' => $result['parent_provider_connection_id'] ?? null,
                'product_plan_provider_route_id' => $result['product_plan_provider_route_id'] ?? null,
                'provider_plan_id_snapshot' => $result['provider_plan_id_snapshot'] ?? null,
                'user_screen_message' => $result['user_message'] ?? null,
                'admin_screen_message' => $result['admin_message'] ?? null,
            ];

            if ($routing === 'successful') {
                $this->settlements->capture($affiliate, $locked->affiliate_cost_snapshot, $locked->txn_reference, 'system', $customer->id);
            } elseif ($routing === 'failed') {
                $this->settlements->release($affiliate, $locked->affiliate_cost_snapshot, $locked->txn_reference, 'system', $customer->id);
                $this->refundCustomer($locked, $customer);
                $updates['status'] = 2;
            }

            $locked->forceFill($updates)->save();

            return $locked->fresh();
        });
    }

    private function refundCustomer(Transaction $transaction, User $customer): void
    {
        $locked = User::withoutGlobalScope('affiliate')->lockForUpdate()->findOrFail($customer->id);
        $before = $this->cents((string) $locked->main_wallet);
        $after = $before + $this->cents((string) $transaction->customer_price_snapshot);
        $locked->forceFill(['main_wallet' => $this->money($after)])->save();
        WalletLog::withoutGlobalScope('affiliate')->create([
            'affiliate_id' => $transaction->affiliate_id, 'user_id' => $locked->id,
            'transaction_id' => $transaction->id, 'action_by' => $locked->id,
            'transaction_category' => 'PARENT_MANAGED_DATA_REFUND',
            'balance_before' => $this->money($before), 'balance_after' => $this->money($after),
            'description' => "Refund for {$transaction->txn_reference}",
        ]);
    }

    private function cents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function money(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
