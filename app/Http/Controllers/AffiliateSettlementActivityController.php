<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateSettlementLedgerEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateSettlementActivityController extends Controller
{
    public function index(Request $request): View
    {
        $affiliate = Affiliate::query()->findOrFail($request->user()->affiliate_id);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:purchase_reservation,purchase_capture,reservation_release,refund,settlement_funding,manual_credit'],
            'direction' => ['nullable', 'in:credit,debit'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $entries = AffiliateSettlementLedgerEntry::query()
            ->where('affiliate_id', $affiliate->id)
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($nested) use ($search) {
                $nested->where('reference', 'like', "%{$search}%")->orWhere('reason', 'like', "%{$search}%");
            }))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('entry_type', $type))
            ->when(($filters['direction'] ?? null) === 'debit', fn ($query) => $query->whereIn('entry_type', ['purchase_reservation', 'purchase_capture']))
            ->when(($filters['direction'] ?? null) === 'credit', fn ($query) => $query->whereNotIn('entry_type', ['purchase_reservation', 'purchase_capture']))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.settlement-wallet.activity', compact('affiliate', 'entries', 'filters'));
    }
}
