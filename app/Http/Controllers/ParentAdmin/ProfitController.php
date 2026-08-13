<?php
namespace App\Http\Controllers\ParentAdmin;
use App\Http\Controllers\Controller; use App\Services\Reporting\ProfitReportService; use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\StreamedResponse;
class ProfitController extends Controller {
 public function index(Request $r, ProfitReportService $s){$q=$s->query($r,$r->user('parent_admin')->parent_business_id);return view('parent-admin.profits.index',array_merge($s->report($q,'parent_profit_snapshot'),['transactions'=>$q->latest()->paginate(50)->withQueryString()]));}
 public function export(Request $r, ProfitReportService $s): StreamedResponse {$rows=$s->query($r,$r->user('parent_admin')->parent_business_id)->latest()->get();return response()->streamDownload(function()use($rows){$f=fopen('php://output','w');fputcsv($f,['Date','Reference','Affiliate','Service','Provider cost','Affiliate cost','Parent profit']);foreach($rows as $x)fputcsv($f,[$x->created_at,$x->txn_reference,$x->affiliate?->name,$x->transaction_category,$x->provider_cost_snapshot,$x->affiliate_cost_snapshot,$x->parent_profit_snapshot]);fclose($f);},'parent-profit-report.csv',['Content-Type'=>'text/csv; charset=UTF-8']);}
}
