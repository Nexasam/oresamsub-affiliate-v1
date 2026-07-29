<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(): View
    {
        return view('platform-admin.transactions.index', ['affiliates' => Affiliate::orderBy('name')->get(['id', 'name'])]);
    }

    public function data(Request $request): JsonResponse
    {
        $request->validate([
            'affiliate_id' => ['nullable', 'exists:affiliates,id'],
            'status' => ['nullable', 'in:-1,0,1,2,3'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'amount_min' => ['nullable', 'numeric', 'min:0'],
            'amount_max' => ['nullable', 'numeric', 'gte:amount_min'],
        ]);

        $query = Transaction::withoutGlobalScope('affiliate')
            ->with(['user:id,first_name,last_name,email', 'affiliate:id,name'])
            ->when($request->affiliate_id, fn (Builder $q, $v) => $q->where('affiliate_id', $v))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->status))
            ->when($request->category, fn (Builder $q, $v) => $q->where('transaction_category', $v))
            ->when($request->date_from, fn (Builder $q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to, fn (Builder $q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($request->filled('amount_min'), fn (Builder $q) => $q->where('amount', '>=', $request->amount_min))
            ->when($request->filled('amount_max'), fn (Builder $q) => $q->where('amount', '<=', $request->amount_max))
            ->when($request->search, fn (Builder $q, $v) => $q->where(function (Builder $q) use ($v) {
                $q->where('txn_reference', 'like', "%{$v}%")
                    ->orWhere('api_id', 'like', "%{$v}%")
                    ->orWhere('phone_number', 'like', "%{$v}%")
                    ->orWhereHas('user', fn (Builder $u) => $u->where('email', 'like', "%{$v}%"));
            }));

        return response()->json([
            'summary' => ['count' => (clone $query)->count(), 'amount' => (float) (clone $query)->sum('amount')],
            'transactions' => $query->latest()->paginate(20),
        ]);
    }
}
