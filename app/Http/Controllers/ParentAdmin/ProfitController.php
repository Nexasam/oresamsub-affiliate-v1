<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Services\Reporting\ProfitReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfitController extends Controller
{
    public function index(Request $request, ProfitReportService $reports)
    {
        $query = $reports->query($request, $request->user('parent_admin')->parent_business_id);

        return view('parent-admin.profits.index', array_merge(
            $reports->report($query, 'parent_profit_snapshot'),
            ['transactions' => $query->latest()->paginate(50)->withQueryString()],
        ));
    }

    public function export(Request $request, ProfitReportService $reports): StreamedResponse
    {
        $rows = $reports->query($request, $request->user('parent_admin')->parent_business_id)->latest()->get();

        return response()->streamDownload(function () use ($rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Reference', 'Affiliate', 'Customer', 'Service', 'Face value', 'Customer paid', 'Affiliate charge', 'Actual provider charge', 'Parent profit']);
            foreach ($rows as $transaction) {
                $customer = trim(($transaction->user?->first_name ?? '').' '.($transaction->user?->last_name ?? '')) ?: $transaction->user?->email;
                fputcsv($file, [
                    $transaction->created_at, $transaction->txn_reference, $transaction->affiliate?->name,
                    $customer, $transaction->transaction_category, $transaction->face_value_snapshot,
                    $transaction->customer_price_snapshot, $transaction->affiliate_cost_snapshot,
                    $transaction->provider_cost_snapshot, $transaction->parent_profit_snapshot,
                ]);
            }
            fclose($file);
        }, 'parent-profit-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
