<?php

namespace App\Http\Controllers;

use App\Models\ParentFundingProvider;
use App\Services\Funding\SettlementVirtualAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Throwable;

class AffiliateSettlementFundingController extends Controller
{
    public function index(): View
    {
        $affiliate = session('affiliate');
        abort_unless($affiliate && $affiliate->parent_business_id, 404);

        return view('admin.settlement-funding.index', [
            'affiliate' => $affiliate,
            'providers' => ParentFundingProvider::query()
                ->where('parent_business_id', $affiliate->parent_business_id)
                ->where('active', true)->where('generation_enabled', true)
                ->with(['fundingProvider', 'banks' => fn ($query) => $query->where('active', true)->where('generation_enabled', true),
                    'parentBusiness'])
                ->get(),
            'accounts' => $affiliate->settlementVirtualAccounts()->with('parentFundingProvider.fundingProvider')->orderBy('bank_name')->get(),
            'entries' => $affiliate->settlementWallet?->ledgerEntries()
                ->whereIn('entry_type', ['settlement_funding', 'manual_credit'])->latest()->limit(50)->get() ?? collect(),
        ]);
    }

    public function generate(ParentFundingProvider $parentFundingProvider, SettlementVirtualAccountService $service): RedirectResponse
    {
        $affiliate = session('affiliate');
        abort_unless($affiliate && (int) $affiliate->parent_business_id === (int) $parentFundingProvider->parent_business_id, 404);
        try {
            $result = $service->generate($affiliate, $parentFundingProvider);
        } catch (ValidationException $exception) {
            return redirect('/admin/settlement-funding')->with('error', collect($exception->errors())->flatten()->first());
        } catch (Throwable $exception) {
            report($exception);

            return redirect('/admin/settlement-funding')->with('error', 'The funding provider could not generate the settlement account. Please try again later or contact your parent administrator.');
        }

        return redirect('/admin/settlement-funding')->with($result['status'] === 1 ? 'success' : 'error', $result['message']);
    }
}
