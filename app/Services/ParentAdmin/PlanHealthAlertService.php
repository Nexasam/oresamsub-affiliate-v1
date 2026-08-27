<?php

namespace App\Services\ParentAdmin;

use App\Models\ParentBusiness;
use App\Models\Transaction;
use App\Support\ProviderProductRegistry;
use Illuminate\Support\Collection;

class PlanHealthAlertService
{
    public function __construct(private readonly ProviderProductRegistry $products) {}

    public function forParent(ParentBusiness $parent, int $limit = 10): Collection
    {
        $thirtyMinutesAgo = now()->subMinutes(30);
        $oneDayAgo = now()->subDay();
        $groups = Transaction::withoutGlobalScope('affiliate')
            ->join('affiliate_product_plans as health_plans', 'health_plans.id', '=', 'transactions.affiliate_product_plan_id')
            ->where('transactions.parent_business_id', $parent->id)
            ->whereIn('transactions.routing_status', ['failed', 'reconciliation_required', 'reconciliation_exhausted'])
            ->where('transactions.created_at', '>=', $oneDayAgo)
            ->selectRaw(
                'health_plans.product_plan_id as source_product_plan_id, transactions.parent_provider_connection_id, COUNT(*) as failure_count, '
                .'SUM(CASE WHEN transactions.created_at >= ? THEN 1 ELSE 0 END) as recent_failure_count, MAX(transactions.id) as latest_transaction_id',
                [$thirtyMinutesAgo]
            )
            ->groupBy('health_plans.product_plan_id', 'transactions.parent_provider_connection_id')
            ->havingRaw('(COUNT(*) >= 5 OR SUM(CASE WHEN transactions.created_at >= ? THEN 1 ELSE 0 END) >= 3)', [$thirtyMinutesAgo])
            ->orderByDesc('latest_transaction_id')
            ->limit($limit)
            ->get();

        if ($groups->isEmpty()) return collect();

        $transactions = Transaction::withoutGlobalScope('affiliate')
            ->with([
                'affiliate:id,name,slug',
                'parentProviderConnection:id,name,base_url',
                'product_plan.product_plan.product_plan_category.product',
                'product_plan.product_plan.providerRoutes.parentProviderConnection.providerConnection.providerAdapter',
                'product_plan.product_plan.providerRoutes.parentProviderConnection.providerAdapter',
            ])
            ->whereIn('id', $groups->pluck('latest_transaction_id'))
            ->get()->keyBy('id');
        $connections = $parent->providerConnections()
            ->where('status', 'active')
            ->where('approval_status', 'approved')
            ->whereHas('providerConnection', fn ($query) => $query->where('status', 'active'))
            ->with(['providerConnection.providerAdapter', 'providerAdapter'])
            ->orderBy('name')
            ->get();

        return $groups->map(function ($group) use ($transactions, $connections) {
            $transaction = $transactions->get($group->latest_transaction_id);
            if (! $transaction) return null;
            $sourcePlan = $transaction->product_plan?->product_plan;
            if (! $sourcePlan) return null;
            $serviceSlug = $this->products->normalize((string) $sourcePlan->product_plan_category?->product?->slug);
            $routeOptions = $connections
                ->map(function ($connection) use ($serviceSlug, $sourcePlan) {
                    $capabilities = $this->products->normalizeCapabilities(
                        $connection->providerConnection?->capabilities
                            ?: $connection->providerConnection?->providerAdapter?->capabilities
                            ?: $connection->providerAdapter?->capabilities
                    );
                    if (! in_array($serviceSlug, $capabilities['services'] ?? [], true)) {
                        return null;
                    }
                    $route = $sourcePlan->providerRoutes->firstWhere('parent_provider_connection_id', $connection->id);

                    return [
                        'connection_id' => $connection->id,
                        'connection_name' => $connection->name,
                        'provider_name' => $connection->providerConnection?->name,
                        'provider_plan_id' => $route?->provider_plan_id,
                        'ready' => filled($route?->provider_plan_id),
                        'current' => $route && (int) $route->priority === 1 && (bool) $route->active,
                    ];
                })
                ->filter()
                ->values();

            return (object) [
                'failure_count' => (int) $group->failure_count,
                'recent_failure_count' => (int) $group->recent_failure_count,
                'transaction' => $transaction,
                'plan' => $sourcePlan,
                'service' => $sourcePlan->product_plan_category?->product?->product_name
                    ?: str($transaction->transaction_category)->replace('_', ' ')->title(),
                'affiliate' => $transaction->affiliate,
                'connection' => $transaction->parentProviderConnection,
                'failure_reason' => $transaction->admin_screen_message ?: 'The provider response requires attention.',
                'provider_website' => $this->providerOrigin($transaction->parentProviderConnection?->base_url),
                'route_options' => $routeOptions,
            ];
        })->filter()->values();
    }

    private function providerOrigin(?string $url): ?string
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) return null;
        $parts = parse_url($url);
        if (! isset($parts['scheme'], $parts['host']) || ! in_array($parts['scheme'], ['http', 'https'], true)) return null;

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
