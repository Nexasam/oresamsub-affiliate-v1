<?php

namespace App\Services\Providers;

use App\Models\AffiliateProductPlan;
use App\Models\Transaction;

class ParentPurchaseExecutor
{
    public function __construct(
        private readonly PurchaseRouteResolver $routes,
        private readonly ConfigurableProviderClient $client,
    ) {}

    /**
     * Execute a purchase using the affiliate's approved parent route.
     *
     * @return array<string, mixed>
     */
    public function execute(AffiliateProductPlan $affiliatePlan, array $runtime): array
    {
        $resolved = $this->routes->resolve($affiliatePlan);
        $runtime['plan'] = $resolved['provider_plan_id'];
        $runtime['provider_plan_id'] = $resolved['provider_plan_id'];

        $result = $this->client->execute(
            $resolved['connection'],
            $resolved['product_slug'],
            $runtime,
        );

        $successful = (bool) ($result['successful'] ?? false);
        $ambiguous = ! $successful && (bool) ($result['ambiguous'] ?? false);

        return array_merge($result, [
            'status' => $successful ? 1 : ($ambiguous ? 0 : 2),
            'retry_count' => 0,
            'user_message' => $result['message'],
            'admin_message' => $result['message'],
            'requires_reconciliation' => $ambiguous,
            'refundable' => ! $successful && ! $ambiguous,
            'parent_business_id' => $resolved['parent']->id,
            'parent_provider_connection_id' => $resolved['connection']->id,
            'product_plan_provider_route_id' => $resolved['route']->id,
            'provider_plan_id_snapshot' => $resolved['provider_plan_id'],
            'provider_reference' => $result['provider_reference'] ?? null,
            'routing_status' => $successful ? 'successful' : ($ambiguous ? 'reconciliation_required' : 'failed'),
        ]);
    }

    /** @return array<string, mixed> */
    public function requery(Transaction $transaction): array
    {
        $transaction->loadMissing('parentProviderConnection');
        $connection = $transaction->parentProviderConnection;
        if (! $connection || ! $transaction->txn_reference) {
            return ['status' => 0, 'successful' => false, 'ambiguous' => true, 'routing_status' => 'reconciliation_required', 'user_message' => 'Transaction requires manual reconciliation.', 'admin_message' => 'The original provider connection or reference is unavailable.'];
        }

        $result = $this->client->requery($connection, $transaction->transaction_category ?: 'data', [
            'reference' => $transaction->txn_reference,
            'provider_reference' => $transaction->provider_reference,
        ]);
        $successful = (bool) ($result['successful'] ?? false);
        $ambiguous = ! $successful && (bool) ($result['ambiguous'] ?? false);

        return array_merge($result, [
            'status' => $successful ? 1 : ($ambiguous ? 0 : 2),
            'routing_status' => $successful ? 'successful' : ($ambiguous ? 'reconciliation_required' : 'failed'),
            'user_message' => $result['message'], 'admin_message' => $result['message'],
            'parent_provider_connection_id' => $connection->id,
            'product_plan_provider_route_id' => $transaction->product_plan_provider_route_id,
            'provider_plan_id_snapshot' => $transaction->provider_plan_id_snapshot,
        ]);
    }
}
