<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateFundingProviderConfig;
use App\Models\FundingModeChangeRequest;
use App\Models\FundingProvider;
use App\Models\ParentBusiness;
use App\Models\ParentFundingProvider;
use App\Models\ParentFundingProviderBank;
use App\Services\Funding\FundingChargeCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FundingProviderController extends Controller
{
    public function __construct(private readonly FundingChargeCalculator $charges) {}

    public function index(Request $request): View
    {
        $parent = $request->user('parent_admin')->parentBusiness;

        return view('parent-admin.funding-providers.index', [
            'catalogue' => FundingProvider::where('active', true)->orderBy('name')->get(),
            'providers' => $parent->fundingProviders()->with('fundingProvider')->withCount(['banks', 'affiliateConfigurations'])->get(),
        ]);
    }

    public function enable(Request $request, FundingProvider $fundingProvider): RedirectResponse
    {
        abort_unless($fundingProvider->active, 404);
        $parent = $request->user('parent_admin')->parentBusiness;
        $data = $request->validate(['credentials' => ['nullable', 'array'], 'webhook_secret' => ['nullable', 'string', 'max:2000'], 'webhook_active' => ['required', 'boolean'], 'active' => ['required', 'boolean'], 'generation_enabled' => ['required', 'boolean']]);
        $provider = $parent->fundingProviders()->firstOrNew(['funding_provider_id' => $fundingProvider->id]);
        if (filled(array_filter($data['credentials'] ?? []))) {
            $provider->credentials = $data['credentials'];
        }
        $provider->active = $data['active'];
        $provider->generation_enabled = $data['generation_enabled'];
        $provider->webhook_active = $data['webhook_active'];
        $provider->webhook_key ??= (string) Str::uuid();
        if (filled($data['webhook_secret'] ?? null)) {
            $provider->webhook_secret = $data['webhook_secret'];
        }
        $provider->save();

        return to_route('parent-admin.funding-providers.index')->with('success', 'Parent funding provider saved.');
    }

    public function banks(Request $request, ParentFundingProvider $parentProvider): View
    {
        $this->authorizeProvider($request, $parentProvider);

        return view('parent-admin.funding-providers.banks', ['provider' => $parentProvider->load('fundingProvider'), 'banks' => $parentProvider->banks()->orderBy('name')->get()]);
    }

    public function storeBank(Request $request, ParentFundingProvider $parentProvider): RedirectResponse
    {
        $this->authorizeProvider($request, $parentProvider);
        $data = $this->bankData($request, $parentProvider);
        $parentProvider->banks()->create($data);

        return back()->with('success', 'Funding bank added.');
    }

    public function updateBank(Request $request, ParentFundingProvider $parentProvider, ParentFundingProviderBank $bank): RedirectResponse
    {
        $this->authorizeProvider($request, $parentProvider);
        abort_unless($bank->parent_funding_provider_id === $parentProvider->id, 404);
        $bank->update($this->bankData($request, $parentProvider, $bank));

        return back()->with('success', 'Funding bank updated.');
    }

    public function affiliates(Request $request, ParentFundingProvider $parentProvider): View
    {
        $parent = $this->authorizeProvider($request, $parentProvider);
        $search = trim((string) $request->query('q'));
        $affiliates = $parent->affiliates()->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('contact_email', 'like', "%{$search}%")))
            ->with(['parentResellerLevel', 'fundingProviderConfigurations' => fn ($query) => $query->where('parent_funding_provider_id', $parentProvider->id)->with('modeChangeRequests')])
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('parent-admin.funding-providers.affiliates', ['provider' => $parentProvider->load('fundingProvider'), 'affiliates' => $affiliates, 'search' => $search]);
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
        $config->webhook_key ??= (string) Str::uuid();
        $config->fill($data)->save();

        return to_route('parent-admin.funding-providers.affiliates.index', $parentProvider)->with('success', 'Affiliate funding configuration saved.');
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

    private function authorizeProvider(Request $request, ParentFundingProvider $provider): ParentBusiness
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        abort_unless($provider->parent_business_id === $parent->id, 404);

        return $parent;
    }

    private function bankData(Request $request, ParentFundingProvider $provider, ?ParentFundingProviderBank $bank = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bank_code' => ['required', 'string', 'max:100', Rule::unique('parent_funding_provider_banks')->where('parent_funding_provider_id', $provider->id)->ignore($bank?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'rate_type' => ['required', Rule::in(['flat', 'percentage'])],
            'rate_value' => ['required', 'numeric', 'min:0'],
            'percentage_cap' => ['nullable', 'numeric', 'min:0'],
            'active' => ['required', 'boolean'], 'generation_enabled' => ['required', 'boolean'],
        ]);
        $this->charges->validate($data['rate_type'], (string) $data['rate_value'], isset($data['percentage_cap']) ? (string) $data['percentage_cap'] : null);

        return $data;
    }
}
