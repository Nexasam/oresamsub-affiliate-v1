<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentAdmin\ResolveTransactionReconciliationRequest;
use App\Models\Affiliate;
use App\Models\Transaction;
use App\Services\Providers\ParentManagedManualPurchaseService;
use App\Services\Providers\ParentPurchaseReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        $affiliateIds = Affiliate::query()->where('parent_business_id', $parent->id)->pluck('id');
        $query = Transaction::withoutGlobalScope('affiliate')
            ->with([
                'user:id,first_name,last_name,email',
                'affiliate:id,name,slug',
                'parentProviderConnection:id,name',
                'product_plan:id,product_plan_id,product_plan_name',
                'product_plan.product_plan:id,product_plan_name',
            ])
            ->where(function ($query) use ($parent, $affiliateIds) {
                $query->where('parent_business_id', $parent->id)
                    ->orWhere(function ($legacy) use ($affiliateIds) {
                        $legacy->whereNull('parent_business_id')->whereIn('affiliate_id', $affiliateIds);
                    });
            })
            ->when($request->filled('affiliate_id'), fn ($query) => $query->where('affiliate_id', $request->integer('affiliate_id')))
            ->when($request->filled('service'), fn ($query) => $query->where('transaction_category', $request->string('service')))
            ->when($request->filled('routing_status'), fn ($query) => $query->where('routing_status', $request->string('routing_status')))
            ->when($request->filled('reference'), fn ($query) => $query->where('txn_reference', 'like', '%'.$request->string('reference').'%'));

        $summaryQuery = clone $query;
        $summary = [
            'count' => (clone $summaryQuery)->count(),
            'volume' => (clone $summaryQuery)->sum('amount'),
            'successful' => (clone $summaryQuery)->where('status', 1)->count(),
            'reconciliation' => (clone $summaryQuery)->where('routing_status', 'reconciliation_required')->count(),
            'manual_review' => (clone $summaryQuery)->whereIn('routing_status', ['manual_pending', 'reconciliation_exhausted'])->count(),
        ];

        return view('parent-admin.transactions.index', [
            'transactions' => $query->latest()->paginate(50)->withQueryString(),
            'affiliates' => Affiliate::query()->where('parent_business_id', $parent->id)->orderBy('name')->get(['id', 'name']),
            'summary' => $summary,
        ]);
    }

    public function completeManual(Request $request, int $transaction, ParentManagedManualPurchaseService $manualPurchases): RedirectResponse
    {
        $validated = $request->validate([
            'outcome' => ['required', Rule::in(['successful', 'failed'])],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);
        $record = Transaction::withoutGlobalScope('affiliate')->findOrFail($transaction);
        $manualPurchases->complete(
            $record,
            $request->user('parent_admin'),
            $validated['outcome'],
            $validated['message'] ?? null,
        );

        return redirect()->route('parent-admin.transactions.index')
            ->with('success', $validated['outcome'] === 'successful'
                ? 'Transaction marked successful and settlement captured.'
                : 'Transaction marked failed; settlement released and customer refunded.');
    }

    public function resolveReconciliation(
        ResolveTransactionReconciliationRequest $request,
        int $transaction,
        ParentPurchaseReconciliationService $reconciliation,
    ): RedirectResponse {
        $record = Transaction::withoutGlobalScope('affiliate')->findOrFail($transaction);
        $reconciliation->resolve($record, $request->user('parent_admin'), $request->validated());

        return redirect()->route('parent-admin.transactions.index')
            ->with('success', $request->validated('outcome') === 'successful'
                ? 'Transaction confirmed successful and settlement captured.'
                : 'Transaction marked failed; settlement released and customer refunded.');
    }
}
