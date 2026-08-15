<?php

namespace App\Services\Providers;

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\ParentAdmin;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletLog;
use App\Services\Pricing\MultiParentPricingResolver;
use App\Services\Wallet\AffiliateSettlementWalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParentManagedManualPurchaseService
{
    public function __construct(
        private readonly MultiParentPricingResolver $pricing,
        private readonly PurchaseRouteResolver $routes,
        private readonly ConfigurableProviderClient $provider,
        private readonly AffiliateSettlementWalletService $settlements,
    ) {}

    public function validateCustomer(User $customer, AffiliateProductPlan $affiliatePlan, array $runtime): array
    {
        $resolved = $this->routes->resolve($affiliatePlan);
        /** @var Affiliate $affiliate */
        $affiliate = $resolved['affiliate'];
        if ((int) $customer->affiliate_id !== (int) $affiliate->id) {
            $this->fail('customer', 'The customer does not belong to this affiliate.');
        }
        $profile = $affiliate->processingProfile;
        if (! $profile || $profile->status !== 'active' || $profile->processing_engine !== 'multi_parent' || $profile->management_mode !== 'parent_managed') {
            $this->fail('processing_profile', 'This affiliate is not enabled for parent-managed processing.');
        }
        $runtime['plan'] = $resolved['provider_plan_id'];
        $runtime['provider_plan_id'] = $resolved['provider_plan_id'];

        return $this->provider->validateCustomer($resolved['connection'], $resolved['product_slug'], $runtime);
    }

    public function submit(User $customer, AffiliateProductPlan $affiliatePlan, array $runtime, int $customerLevel, ?string $faceAmount = null): Transaction
    {
        $reference = trim((string) ($runtime['reference'] ?? ''));
        if ($reference === '') {
            $this->fail('reference', 'A unique purchase reference is required.');
        }
        if ($existing = Transaction::withoutGlobalScope('affiliate')->where('txn_reference', $reference)->first()) {
            return $existing;
        }

        $resolved = $this->routes->resolve($affiliatePlan);
        /** @var Affiliate $affiliate */
        $affiliate = $resolved['affiliate'];
        $service = (string) $resolved['product_slug'];
        if (! in_array($service, ['cable_subscription', 'utility_bills'], true)) {
            $this->fail('service', 'Only cable and electricity can use manual pending processing.');
        }
        if ((int) $customer->affiliate_id !== (int) $affiliate->id) {
            $this->fail('customer', 'The customer does not belong to this affiliate.');
        }
        $profile = $affiliate->processingProfile;
        if (! $profile || $profile->status !== 'active' || $profile->processing_engine !== 'multi_parent' || $profile->management_mode !== 'parent_managed') {
            $this->fail('processing_profile', 'This affiliate is not enabled for parent-managed processing.');
        }

        $runtime['plan'] = $resolved['provider_plan_id'];
        $runtime['provider_plan_id'] = $resolved['provider_plan_id'];
        $validation = $this->provider->validateCustomer($resolved['connection'], $service, $runtime);
        if (! ($validation['successful'] ?? false)) {
            $this->fail('customer_validation', (string) ($validation['message'] ?? 'The customer details could not be validated.'));
        }

        $price = $this->pricing->resolve($affiliate, $affiliatePlan, $customerLevel, $faceAmount);

        return DB::transaction(function () use ($customer, $affiliate, $affiliatePlan, $runtime, $reference, $resolved, $service, $validation, $price) {
            $lockedCustomer = User::withoutGlobalScope('affiliate')->lockForUpdate()->findOrFail($customer->id);
            $before = $this->cents((string) $lockedCustomer->main_wallet);
            $charge = $this->cents($price['customer_selling_price']);
            if ($before < $charge) {
                $this->fail('wallet', 'Insufficient customer wallet balance.');
            }

            $this->settlements->reserve($affiliate, $price['affiliate_acquisition_price'], $reference, 'user', $lockedCustomer->id);
            $after = $before - $charge;
            $lockedCustomer->forceFill(['main_wallet' => $this->money($after)])->save();

            $customerName = $validation['customer_name'] ?? null;
            $customerAddress = $validation['customer_address'] ?? null;
            $transaction = Transaction::withoutGlobalScope('affiliate')->create([
                'affiliate_id' => $affiliate->id,
                'parent_business_id' => $affiliate->parent_business_id,
                'parent_provider_connection_id' => $resolved['connection']->id,
                'product_plan_provider_route_id' => $resolved['route']->id,
                'provider_plan_id_snapshot' => $resolved['provider_plan_id'],
                'api_id' => (string) ($resolved['product_plan']->api_id ?? $resolved['product_plan']->id),
                'txn_reference' => $reference,
                'affiliate_product_plan_id' => $affiliatePlan->id,
                'user_id' => $lockedCustomer->id,
                'transaction_category' => $service,
                'wallet_category' => 'main_wallet',
                'smart_card_number' => $runtime['smartcard_number'] ?? $runtime['smart_card_number'] ?? null,
                'metre_number' => $runtime['meter_number'] ?? $runtime['metre_number'] ?? null,
                'validation_address' => is_scalar($customerAddress) ? (string) $customerAddress : null,
                'extra_info' => json_encode(['validation_customer_name' => $customerName, 'validation_address' => $customerAddress]),
                'amount' => $price['customer_selling_price'],
                'discounted_amount' => $price['customer_selling_price'],
                'balance_before' => $this->money($before),
                'balance_after' => $this->money($after),
                'description' => 'Parent-managed manual '.str_replace('_', ' ', $service).' purchase',
                'status' => 0,
                'routing_status' => 'manual_pending',
                'user_screen_message' => 'Customer confirmed. Transaction is pending manual processing.',
                'admin_screen_message' => 'Validated and awaiting parent manual processing.',
                'provider_response' => ['validation' => [
                    'message' => $validation['message'] ?? null,
                    'customer_name' => $customerName,
                    'customer_address' => $customerAddress,
                    'response' => $validation['provider_response'] ?? null,
                ]],
                'provider_cost_snapshot' => $price['provider_cost'],
                'parent_cost_snapshot' => $price['provider_cost'],
                'affiliate_cost_snapshot' => $price['affiliate_acquisition_price'],
                'customer_price_snapshot' => $price['customer_selling_price'],
                'parent_profit_snapshot' => $price['parent_profit'],
                'affiliate_profit_snapshot' => $price['affiliate_profit'],
            ]);

            WalletLog::withoutGlobalScope('affiliate')->create([
                'affiliate_id' => $affiliate->id,
                'user_id' => $lockedCustomer->id,
                'transaction_id' => $transaction->id,
                'action_by' => $lockedCustomer->id,
                'transaction_category' => 'PARENT_MANAGED_'.strtoupper($service).'_DEBIT',
                'balance_before' => $this->money($before),
                'balance_after' => $this->money($after),
                'description' => 'Manual '.str_replace('_', ' ', $service)." purchase {$reference}",
            ]);

            return $transaction;
        });
    }

    public function complete(Transaction $transaction, ParentAdmin $actor, string $outcome, ?string $message = null): Transaction
    {
        if (! in_array($outcome, ['successful', 'failed'], true)) {
            $this->fail('outcome', 'The manual outcome must be successful or failed.');
        }
        if ((int) $transaction->parent_business_id !== (int) $actor->parent_business_id) {
            $this->fail('transaction', 'You cannot process a transaction owned by another parent business.');
        }

        return DB::transaction(function () use ($transaction, $actor, $outcome, $message) {
            $locked = Transaction::withoutGlobalScope('affiliate')->lockForUpdate()->findOrFail($transaction->id);
            if ((int) $locked->parent_business_id !== (int) $actor->parent_business_id) {
                $this->fail('transaction', 'You cannot process a transaction owned by another parent business.');
            }
            if (in_array($locked->routing_status, ['manual_successful', 'manual_failed'], true)) {
                return $locked;
            }
            if ($locked->routing_status !== 'manual_pending' || ! in_array($locked->transaction_category, ['cable_subscription', 'utility_bills'], true)) {
                $this->fail('transaction', 'Only a manual-pending cable or electricity transaction can be completed.');
            }

            $affiliate = Affiliate::query()->findOrFail($locked->affiliate_id);
            $customer = User::withoutGlobalScope('affiliate')->findOrFail($locked->user_id);
            if ($outcome === 'successful') {
                $this->settlements->capture($affiliate, $locked->affiliate_cost_snapshot, $locked->txn_reference, 'parent_admin', $actor->id);
                $locked->forceFill([
                    'status' => 1,
                    'routing_status' => 'manual_successful',
                    'user_screen_message' => $message ?: 'Transaction completed successfully.',
                    'admin_screen_message' => $message ?: 'Manually completed successfully.',
                    'set_for_manual' => 1,
                    'manually_processed_by' => $actor->id,
                ])->save();
            } else {
                $this->settlements->release($affiliate, $locked->affiliate_cost_snapshot, $locked->txn_reference, 'parent_admin', $actor->id);
                $this->refundCustomer($locked, $customer);
                $locked->forceFill([
                    'status' => 2,
                    'routing_status' => 'manual_failed',
                    'refund_reason' => $message ?: 'Manual processing failed.',
                    'user_screen_message' => $message ?: 'Transaction failed and your wallet was refunded.',
                    'admin_screen_message' => $message ?: 'Manually failed and refunded.',
                    'set_for_manual' => 1,
                    'manually_processed_by' => $actor->id,
                ])->save();
            }

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
            'affiliate_id' => $transaction->affiliate_id,
            'user_id' => $locked->id,
            'transaction_id' => $transaction->id,
            'action_by' => $locked->id,
            'transaction_category' => 'PARENT_MANAGED_'.strtoupper((string) $transaction->transaction_category).'_REFUND',
            'balance_before' => $this->money($before),
            'balance_after' => $this->money($after),
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

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
