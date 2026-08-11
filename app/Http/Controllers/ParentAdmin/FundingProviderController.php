<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateFundingProviderConfig;
use App\Models\FundingModeChangeRequest;
use App\Models\FundingProvider;
use App\Models\ParentFundingProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FundingProviderController extends Controller
{
    public function index(Request $request): View
    {
        $parent = $request->user('parent_admin')->parentBusiness;

        return view('parent-admin.funding-providers.index', [
            'catalogue' => FundingProvider::where('active', true)->orderBy('name')->get(),
            'providers' => $parent->fundingProviders()->with(['fundingProvider', 'affiliateConfigurations.affiliate', 'affiliateConfigurations.modeChangeRequests' => fn ($query) => $query->where('status', 'pending')])->get(),
            'affiliates' => $parent->affiliates()->orderBy('name')->get(),
        ]);
    }

    public function enable(Request $request, FundingProvider $fundingProvider): RedirectResponse
    {
        abort_unless($fundingProvider->active, 404);
        $parent = $request->user('parent_admin')->parentBusiness;
        $data = $request->validate(['credentials' => ['nullable', 'array'], 'active' => ['required', 'boolean'], 'generation_enabled' => ['required', 'boolean']]);
        $provider = $parent->fundingProviders()->firstOrNew(['funding_provider_id' => $fundingProvider->id]);
        if (filled(array_filter($data['credentials'] ?? []))) {
            $provider->credentials = $data['credentials'];
        }
        $provider->active = $data['active'];
        $provider->generation_enabled = $data['generation_enabled'];
        $provider->save();

        return to_route('parent-admin.funding-providers.index')->with('success', 'Parent funding provider saved.');
    }

    public function configureAffiliate(Request $request, ParentFundingProvider $parentProvider, Affiliate $affiliate): RedirectResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        abort_unless($parentProvider->parent_business_id === $parent->id && $affiliate->parent_business_id === $parent->id, 404);
        $data = $request->validate([
            'management_mode' => ['required', Rule::in(['parent_managed', 'affiliate_managed'])],
            'active' => ['required', 'boolean'], 'generation_enabled' => ['required', 'boolean'],
            'bank_codes' => ['nullable', 'array'], 'bank_codes.*' => ['required', 'string', 'max:100', 'distinct'],
        ]);
        $config = AffiliateFundingProviderConfig::firstOrNew(['affiliate_id' => $affiliate->id, 'parent_funding_provider_id' => $parentProvider->id]);
        if ($config->exists && $config->management_mode !== $data['management_mode']) {
            FundingModeChangeRequest::updateOrCreate(
                ['affiliate_funding_provider_config_id' => $config->id, 'status' => 'pending'],
                ['requested_mode' => $data['management_mode']]
            );
            unset($data['management_mode']);
        }
        $data['bank_codes'] = $this->normalizeBankCodes($data['bank_codes'] ?? []);
        $config->fill($data)->save();

        return to_route('parent-admin.funding-providers.index')->with('success', 'Affiliate funding configuration saved.');
    }

    public function reviewMode(Request $request, FundingModeChangeRequest $modeRequest): RedirectResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        $modeRequest->load('affiliateConfiguration.parentFundingProvider');
        abort_unless($modeRequest->affiliateConfiguration?->parentFundingProvider?->parent_business_id === $parent->id && $modeRequest->status === 'pending', 404);
        $data = $request->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])], 'review_note' => ['nullable', 'string', 'max:1000']]);

        DB::transaction(function () use ($modeRequest, $data, $request) {
            if ($data['decision'] === 'approved') {
                $modeRequest->affiliateConfiguration->update(['management_mode' => $modeRequest->requested_mode]);
            }
            $modeRequest->update(['status' => $data['decision'], 'review_note' => $data['review_note'] ?? null, 'reviewed_by_parent_admin_id' => $request->user('parent_admin')->id, 'reviewed_at' => now()]);
        });

        return to_route('parent-admin.funding-providers.index')->with('success', 'Funding mode request reviewed.');
    }

    private function normalizeBankCodes(array $values): array
    {
        return collect($values)->flatMap(fn ($value) => explode(',', $value))->map(fn ($value) => trim($value))->filter()->unique()->values()->all();
    }
}
