<?php
namespace App\Http\Controllers;
use App\Services\Reporting\ProfitReportService; use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\StreamedResponse;
class AffiliateProfitReportController extends Controller {
 public function index(Request $r,ProfitReportService $s){$a=session('affiliate');abort_unless($a,404);$q=$s->query($r,null,$a->id);return view('admin.profits.index',array_merge($s->report($q,'affiliate_profit_snapshot'),['transactions'=>$q->latest()->paginate(50)->withQueryString()]));}
 public function export(Request $r,ProfitReportService $s):StreamedResponse{$a=session('affiliate');abort_unless($a,404);$rows=$s->query($r,null,$a->id)->latest()->get();return response()->streamDownload(function()use($rows){$f=fopen('php://output','w');fputcsv($f,['Date','Reference','Service','Customer price','Acquisition cost','Affiliate profit']);foreach($rows as$x)fputcsv($f,[$x->created_at,$x->txn_reference,$x->transaction_category,$x->customer_price_snapshot,$x->affiliate_cost_snapshot,$x->affiliate_profit_snapshot]);fclose($f);},'affiliate-profit-report.csv',['Content-Type'=>'text/csv; charset=UTF-8']);}
}
