<?php

namespace App\Http\Controllers;

use App\Services\Reporting\ProfitReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AffiliateProfitReportController extends Controller
{
    public function index(Request $request, ProfitReportService $reports)
    {
        $affiliate = session('affiliate');
        abort_unless($affiliate, 404);
        $query = $reports->query($request, null, $affiliate->id);

        return view('admin.profits.index', array_merge(
            $reports->report($query, 'affiliate_profit_snapshot'),
            ['transactions' => $query->latest()->paginate(50)->withQueryString()],
        ));
    }

    public function export(Request $request, ProfitReportService $reports): StreamedResponse
    {
        $affiliate = session('affiliate');
        abort_unless($affiliate, 404);
        $rows = $reports->query($request, null, $affiliate->id)->latest()->get();

        return response()->streamDownload(function () use ($rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Reference', 'Customer', 'Service', 'Face value', 'Customer paid', 'Acquisition cost', 'Affiliate profit']);
            foreach ($rows as $transaction) {
                $customer = trim(($transaction->user?->first_name ?? '').' '.($transaction->user?->last_name ?? '')) ?: $transaction->user?->email;
                fputcsv($file, [
                    $transaction->created_at, $transaction->txn_reference, $customer,
                    $transaction->transaction_category, $transaction->face_value_snapshot,
                    $transaction->customer_price_snapshot, $transaction->affiliate_cost_snapshot,
                    $transaction->affiliate_profit_snapshot,
                ]);
            }
            fclose($file);
        }, 'affiliate-profit-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
