<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $transactionQuery = Transaction::withoutGlobalScope('affiliate');

        return view('platform-admin.dashboard', [
            'stats' => [
                'affiliates' => Affiliate::count(),
                'activeAffiliates' => Affiliate::where('activation_status', 1)->count(),
                'users' => User::withoutGlobalScope('affiliate')->count(),
                'transactions' => (clone $transactionQuery)->count(),
                'volume' => (float) (clone $transactionQuery)->where('status', 1)->sum('amount'),
            ],
            'affiliates' => Affiliate::withCount([
                'users' => fn ($query) => $query->withoutGlobalScope('affiliate'),
                'transactions' => fn ($query) => $query->withoutGlobalScope('affiliate'),
            ])->latest()->limit(6)->get(),
        ]);
    }
}
