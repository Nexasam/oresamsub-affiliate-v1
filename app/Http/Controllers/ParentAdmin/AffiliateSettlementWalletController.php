<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentAdmin\CreditAffiliateSettlementWalletRequest;
use App\Models\Affiliate;
use App\Models\AffiliateSettlementWallet;
use App\Services\Wallet\AffiliateSettlementWalletService;
use App\Services\AffiliateProcessingProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AffiliateSettlementWalletController extends Controller
{
    public function index(): View
    {
        $parent = request()->user('parent_admin')->parentBusiness;

        return view('parent-admin.affiliates.settlement-wallets', [
            'affiliates' => $parent->affiliates()->with(['settlementWallet', 'processingProfile'])->orderBy('name')->paginate(30),
        ]);
    }

    public function show(Affiliate $affiliate, AffiliateProcessingProfileService $profiles): View
    {
        $wallet = AffiliateSettlementWallet::query()->firstOrCreate(
            ['affiliate_id' => $affiliate->id],
            ['parent_business_id' => $affiliate->parent_business_id, 'currency' => 'NGN', 'available_balance' => '0.00', 'reserved_balance' => '0.00', 'status' => 'active'],
        );

        return view('parent-admin.affiliates.settlement-wallet', [
            'affiliate' => $affiliate,
            'wallet' => $wallet,
            'entries' => $wallet->ledgerEntries()->latest()->paginate(25),
            'processingProfile' => $profiles->ensure($affiliate),
            'processingConnections' => $affiliate->parentBusiness->providerConnections()
                ->where('status', 'active')->where('approval_status', 'approved')->orderBy('name')->get(),
        ]);
    }

    public function credit(CreditAffiliateSettlementWalletRequest $request, Affiliate $affiliate, AffiliateSettlementWalletService $service): RedirectResponse
    {
        $data = $request->validated();
        $service->credit($affiliate, $request->user('parent_admin'), (string) $data['amount'], $data['reference'], $data['reason']);

        return redirect()->route('parent-admin.affiliates.settlement-wallet.show', $affiliate)
            ->with('success', 'Affiliate settlement wallet credited successfully.');
    }
}
