<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\FundingProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FundingProviderController extends Controller
{
    public function index(): View
    {
        $this->seedInitialProviders();

        return view('platform-admin.funding-providers.index', ['providers' => FundingProvider::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        FundingProvider::create($this->validated($request));

        return to_route('platform-admin.funding-providers.index')->with('success', 'Funding provider created.');
    }

    public function update(Request $request, FundingProvider $fundingProvider): RedirectResponse
    {
        $fundingProvider->update($this->validated($request, $fundingProvider));

        return to_route('platform-admin.funding-providers.index')->with('success', 'Funding provider updated.');
    }

    private function validated(Request $request, ?FundingProvider $provider = null): array
    {
        if (is_string($request->input('credential_fields'))) {
            $request->merge(['credential_fields' => collect(explode(',', $request->input('credential_fields')))->map(fn (string $field) => trim($field))->filter()->values()->all()]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:100', Rule::unique('funding_providers')->ignore($provider?->id)],
            'adapter_key' => ['required', 'alpha_dash', 'max:100', Rule::unique('funding_providers')->ignore($provider?->id)],
            'credential_fields' => ['nullable', 'array'],
            'credential_fields.*' => ['required', 'alpha_dash', 'distinct'],
            'active' => ['required', 'boolean'],
        ]);
    }

    private function seedInitialProviders(): void
    {
        foreach ([['Xixapay', 'xixapay'], ['SecurewaveNG', 'securewaveng']] as [$name, $slug]) {
            $provider = FundingProvider::firstOrCreate(['slug' => $slug], [
                'name' => $name, 'adapter_key' => $slug,
                'credential_fields' => ['api_public_key', 'api_secret_key'], 'active' => true,
            ]);
            if ($slug === 'securewaveng') {
                $fields = collect($provider->credential_fields ?? [])
                    ->map(fn ($field) => $field === 'contract_code' ? 'business_id' : $field)
                    ->push('business_id')->unique()->values()->all();
                if ($fields !== ($provider->credential_fields ?? [])) {
                    $provider->update(['credential_fields' => $fields]);
                }
            }
        }
    }
}
