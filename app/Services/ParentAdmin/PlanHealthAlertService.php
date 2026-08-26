<?php

namespace App\Services\ParentAdmin;

use App\Models\ParentBusiness;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class PlanHealthAlertService
{
    public function forParent(ParentBusiness $parent, int $days = 7, int $limit = 10): Collection
    {
        $groups = Transaction::withoutGlobalScope('affiliate')
            ->where('parent_business_id', $parent->id)
            ->whereNotNull('affiliate_product_plan_id')
            ->whereIn('routing_status', ['failed', 'reconciliation_required', 'reconciliation_exhausted'])
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('affiliate_product_plan_id, parent_provider_connection_id, COUNT(*) as failure_count, MAX(id) as latest_transaction_id')
            ->groupBy('affiliate_product_plan_id', 'parent_provider_connection_id')
            ->orderByDesc('latest_transaction_id')
            ->limit($limit)
            ->get();

        if ($groups->isEmpty()) return collect();

        $transactions = Transaction::withoutGlobalScope('affiliate')
            ->with([
                'affiliate:id,name,slug',
                'parentProviderConnection:id,name,base_url',
                'product_plan.product_plan.product_plan_category.product',
            ])
            ->whereIn('id', $groups->pluck('latest_transaction_id'))
            ->get()->keyBy('id');

        return $groups->map(function ($group) use ($transactions) {
            $transaction = $transactions->get($group->latest_transaction_id);
            if (! $transaction) return null;
            $sourcePlan = $transaction->product_plan?->product_plan;
            if (! $sourcePlan) return null;

            return (object) [
                'failure_count' => (int) $group->failure_count,
                'transaction' => $transaction,
                'plan' => $sourcePlan,
                'service' => $sourcePlan->product_plan_category?->product?->product_name
                    ?: str($transaction->transaction_category)->replace('_', ' ')->title(),
                'affiliate' => $transaction->affiliate,
                'connection' => $transaction->parentProviderConnection,
                'failure_reason' => $transaction->admin_screen_message ?: 'The provider response requires attention.',
                'provider_website' => $this->providerOrigin($transaction->parentProviderConnection?->base_url),
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
