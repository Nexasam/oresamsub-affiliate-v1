<?php

namespace App\Http\Controllers;

use App\Models\AffiliateFundingProviderConfig;
use App\Models\FundingModeChangeRequest;
use App\Models\ParentFundingProviderBank;
use App\Services\Funding\FundingChargeCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AffiliateFundingProviderController extends Controller
{
    public function __construct(private readonly FundingChargeCalculator $charges) {}

    public function index(): View
    {
        $affiliate = session('affiliate');
        abort_unless($affiliate, 404);

        return view('admin.funding-providers.index', [
            'configs' => AffiliateFundingProviderConfig::query()
                ->where('affiliate_id', $affiliate->id)
                ->whereHas('parentFundingProvider', fn ($query) => $query->where('active', true))
                ->with(['parentFundingProvider.fundingProvider', 'parentFundingProvider.banks' => fn ($query) => $query->where('active', true)->orderBy('name'), 'banks', 'modeChangeRequests' => fn ($query) => $query->where('status', 'pending')])
                ->get(),
        ]);
    }

    public function update(Request $request, AffiliateFundingProviderConfig $config): RedirectResponse
    {
        $this->authorizeAffiliate($config);
        $data = $request->validate([
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:2000'],
            'webhook_secret' => ['nullable', 'string', 'max:2000'],
            'webhook_active' => ['required', 'boolean'],
            'banks' => ['nullable', 'array'],
            'banks.*.parent_funding_provider_bank_id' => ['required', 'integer'],
            'banks.*.rate_type' => ['required', Rule::in(['flat', 'percentage'])],
            'banks.*.rate_value' => ['required', 'numeric', 'min:0'],
            'banks.*.percentage_cap' => ['nullable', 'numeric', 'min:0'],
            'banks.*.active' => ['required', 'boolean'],
            'banks.*.generation_enabled' => ['required', 'boolean'],
        ]);

        if (! filled(array_filter($data['credentials'] ?? []))) {
            unset($data['credentials']);
        }
        if (! filled($data['webhook_secret'] ?? null)) {
            unset($data['webhook_secret']);
        }
        $banks = $data['banks'] ?? [];
        unset($data['banks']);
        $data['webhook_key'] = $config->webhook_key ?? (string) Str::uuid();

        DB::transaction(function () use ($config, $data, $banks) {
            $config->update($data);
            foreach ($banks as $bank) {
                $parentBank = ParentFundingProviderBank::where('parent_funding_provider_id', $config->parent_funding_provider_id)->findOrFail($bank['parent_funding_provider_bank_id']);
                $this->charges->validate($bank['rate_type'], (string) $bank['rate_value'], isset($bank['percentage_cap']) ? (string) $bank['percentage_cap'] : null);
                $config->banks()->updateOrCreate(['parent_funding_provider_bank_id' => $parentBank->id], $bank);
            }
        });

        return redirect('/admin/affiliate-funding-providers')->with('success', 'Funding details saved.');
    }

    public function requestMode(Request $request, AffiliateFundingProviderConfig $config): RedirectResponse
    {
        $this->authorizeAffiliate($config);
        $data = $request->validate(['requested_mode' => ['required', Rule::in(['affiliate_managed']), Rule::notIn([$config->management_mode])]]);
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
