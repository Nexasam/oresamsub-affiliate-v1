<?php

namespace App\Services\Providers;

use App\Models\Affiliate;
use App\Models\ParentAdmin;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ParentPurchaseReconciliationService
{
    public function __construct(private readonly ParentManagedPurchaseOrchestrator $orchestrator) {}

    public function resolve(Transaction $transaction, ParentAdmin $actor, array $attributes): Transaction
    {
        if ((int) $transaction->parent_business_id !== (int) $actor->parent_business_id) {
            $this->fail('transaction', 'You cannot resolve a transaction owned by another parent business.');
        }
        if (! in_array($transaction->routing_status, ['reconciliation_required', 'reconciliation_exhausted'], true)) {
            $this->fail('transaction', 'Only a transaction awaiting reconciliation can be resolved manually.');
        }

        $affiliate = Affiliate::query()->where('parent_business_id', $actor->parent_business_id)->findOrFail($transaction->affiliate_id);
        $customer = User::withoutGlobalScope('affiliate')->where('affiliate_id', $affiliate->id)->findOrFail($transaction->user_id);
        $successful = $attributes['outcome'] === 'successful';
        $providerReference = filled($attributes['provider_reference'] ?? null)
            ? trim($attributes['provider_reference'])
            : $transaction->provider_reference;
        $note = trim($attributes['note']);

        $resolved = $this->orchestrator->finalize($transaction, $affiliate, $customer, [
            'status' => $successful ? 1 : 2,
            'successful' => $successful,
            'ambiguous' => false,
            'routing_status' => $successful ? 'successful' : 'failed',
            'user_message' => $successful
                ? 'Transaction completed successfully.'
                : 'Transaction failed and your wallet was refunded.',
            'admin_message' => 'Manually reconciled by parent admin: '.$note,
            'provider_reference' => $providerReference,
            'parent_provider_connection_id' => $transaction->parent_provider_connection_id,
            'product_plan_provider_route_id' => $transaction->product_plan_provider_route_id,
            'provider_plan_id_snapshot' => $transaction->provider_plan_id_snapshot,
            'provider_response' => $transaction->provider_response,
        ]);

        $resolved->forceFill([
            'set_for_manual' => 1,
            'manually_processed_by' => $actor->id,
            'provider_reference' => $providerReference,
            'admin_screen_message' => 'Manually reconciled by parent admin: '.$note,
            'refund_reason' => $successful ? $resolved->refund_reason : $note,
        ])->save();

        return $resolved->fresh();
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
