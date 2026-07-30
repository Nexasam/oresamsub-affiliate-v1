<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('platform-admin.reports.index', [
            'affiliates' => Affiliate::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $data = $request->validate([
            'affiliate_id' => ['nullable', 'exists:affiliates,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = Transaction::withoutGlobalScope('affiliate')
            ->where('status', 1)
            ->with(['affiliate:id,name', 'user:id,first_name,last_name,email'])
            ->when($data['affiliate_id'] ?? null, fn (Builder $query, $affiliate) => $query->where('affiliate_id', $affiliate))
            ->when($data['date_from'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($data['date_to'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date));

        $summary = [
            'transactions' => 0,
            'revenue' => 0.0,
            'provider_cost' => 0.0,
            'commissions' => 0.0,
            'estimated_profit' => 0.0,
            'cost_coverage' => 0,
        ];
        $affiliateTotals = [];
        $affiliateNames = Affiliate::pluck('name', 'id');

        foreach ((clone $query)->select([
            'id', 'affiliate_id', 'amount', 'discounted_amount', 'automation_plan_amount', 'referral_commission_value',
        ])->cursor() as $transaction) {
            $revenue = is_numeric($transaction->discounted_amount)
                ? (float) $transaction->discounted_amount
                : (float) $transaction->amount;
            $commission = is_numeric($transaction->referral_commission_value)
                ? (float) $transaction->referral_commission_value
                : 0.0;

            $summary['transactions']++;
            $summary['revenue'] += $revenue;
            $summary['commissions'] += $commission;
            $affiliateTotals[$transaction->affiliate_id] ??= [
                'affiliate_id' => $transaction->affiliate_id,
                'name' => $affiliateNames[$transaction->affiliate_id] ?? 'Unknown affiliate',
                'transactions' => 0,
                'revenue' => 0.0,
            ];
            $affiliateTotals[$transaction->affiliate_id]['transactions']++;
            $affiliateTotals[$transaction->affiliate_id]['revenue'] += $revenue;

            if (is_numeric($transaction->automation_plan_amount)) {
                $cost = (float) $transaction->automation_plan_amount;
                $summary['provider_cost'] += $cost;
                $summary['estimated_profit'] += $revenue - $cost - $commission;
                $summary['cost_coverage']++;
            }
        }

        $summary['coverage_percent'] = $summary['transactions']
            ? round(($summary['cost_coverage'] / $summary['transactions']) * 100, 1)
            : 0;

        return response()->json([
            'summary' => $summary,
            'by_affiliate' => collect($affiliateTotals)->sortByDesc('revenue')->values(),
            'recent' => (clone $query)->latest()->limit(50)->get(),
        ]);
    }
}
