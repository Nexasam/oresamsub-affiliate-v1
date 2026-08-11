<?php

namespace App\Http\Controllers;

use App\Models\AffiliateFundingProviderConfig;
use App\Models\FundingModeChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AffiliateFundingProviderController extends Controller
{
    public function index(): View
    {
        $affiliate = session('affiliate');
        abort_unless($affiliate, 404);

        return view('admin.funding-providers.index', [
            'configs' => AffiliateFundingProviderConfig::query()
                ->where('affiliate_id', $affiliate->id)
                ->whereHas('parentFundingProvider', fn ($query) => $query->where('active', true))
                ->with(['parentFundingProvider.fundingProvider', 'modeChangeRequests' => fn ($query) => $query->where('status', 'pending')])
                ->get(),
        ]);
    }

    public function update(Request $request, AffiliateFundingProviderConfig $config): RedirectResponse
    {
        $this->authorizeAffiliate($config);
        $data = $request->validate([
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:2000'],
            'bank_codes' => ['nullable', 'array'],
            'bank_codes.*' => ['required', 'string', 'max:100', 'distinct'],
        ]);

        if (! filled(array_filter($data['credentials'] ?? []))) {
            unset($data['credentials']);
        }
        $data['bank_codes'] = collect($data['bank_codes'] ?? [])->flatMap(fn ($value) => explode(',', $value))->map(fn ($value) => trim($value))->filter()->unique()->values()->all();
        $config->update($data);

        return redirect('/admin/affiliate-funding-providers')->with('success', 'Funding details saved.');
    }

    public function requestMode(Request $request, AffiliateFundingProviderConfig $config): RedirectResponse
    {
        $this->authorizeAffiliate($config);
        $data = $request->validate(['requested_mode' => ['required', Rule::in(['parent_managed', 'affiliate_managed']), Rule::notIn([$config->management_mode])]]);
        FundingModeChangeRequest::updateOrCreate(
            ['affiliate_funding_provider_config_id' => $config->id, 'status' => 'pending'],
            ['requested_mode' => $data['requested_mode']]
        );

        return redirect('/admin/affiliate-funding-providers')->with('success', 'Mode switch submitted for parent approval.');
    }

    private function authorizeAffiliate(AffiliateFundingProviderConfig $config): void
    {
        abort_unless((int) $config->affiliate_id === (int) session('affiliate')?->id, 404);
    }
}
