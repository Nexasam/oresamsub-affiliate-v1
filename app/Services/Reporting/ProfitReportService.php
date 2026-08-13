<?php

namespace App\Services\Reporting;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProfitReportService
{
    public function query(Request $request, ?int $parentId = null, ?int $affiliateId = null): Builder
    {
        return Transaction::withoutGlobalScope('affiliate')->with(['affiliate:id,name', 'user:id,first_name,last_name,email'])
            ->where('routing_status', 'successful')->where('status', 1)
            ->whereNotNull('provider_cost_snapshot')->whereNotNull('affiliate_cost_snapshot')->whereNotNull('customer_price_snapshot')
            ->when($parentId, fn ($q) => $q->where('parent_business_id', $parentId))
            ->when($affiliateId, fn ($q) => $q->where('affiliate_id', $affiliateId))
            ->when($request->filled('service'), fn ($q) => $q->where('transaction_category', $request->string('service')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')));
    }

    public function report(Builder $query, string $profitColumn): array
    {
        $summary = ['transactions' => (clone $query)->count(), 'sales' => (clone $query)->sum('customer_price_snapshot'), 'cost' => (clone $query)->sum($profitColumn === 'parent_profit_snapshot' ? 'provider_cost_snapshot' : 'affiliate_cost_snapshot'), 'profit' => (clone $query)->sum($profitColumn)];
        $services = (clone $query)->selectRaw("transaction_category, COUNT(*) transactions, SUM({$profitColumn}) profit")->groupBy('transaction_category')->orderByDesc('profit')->get();
        return compact('summary', 'services');
    }
}
