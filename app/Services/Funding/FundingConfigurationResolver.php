<?php

namespace App\Services\Funding;

use App\Models\Affiliate;
use App\Models\AffiliateFundingProviderConfig;
use Illuminate\Validation\ValidationException;

class FundingConfigurationResolver
{
    public function resolveForGeneration(Affiliate $affiliate, string $providerSlug): ?array
    {
        if (! config('parent_businesses.features.multi_parent_funding', false)) {
            return null;
        }

        $config = AffiliateFundingProviderConfig::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('active', true)
            ->where('generation_enabled', true)
            ->whereHas('parentFundingProvider', fn ($query) => $query
                ->where('parent_business_id', $affiliate->parent_business_id)
                ->where('active', true)
                ->where('generation_enabled', true)
                ->whereHas('fundingProvider', fn ($provider) => $provider->where('slug', $providerSlug)->where('active', true)))
            ->with([
                'parentFundingProvider.fundingProvider',
                'parentFundingProvider.banks' => fn ($query) => $query->where('active', true)->where('generation_enabled', true),
                'banks' => fn ($query) => $query->where('active', true)->where('generation_enabled', true)->with('parentBank'),
            ])
            ->first();

        if (! $config) {
            throw ValidationException::withMessages(['funding_provider' => 'New virtual-account generation is unavailable for this provider.']);
        }

        $credentials = $config->management_mode === 'affiliate_managed'
            ? $config->credentials
            : $config->parentFundingProvider->credentials;

        if (empty(array_filter($credentials ?? []))) {
            throw ValidationException::withMessages(['funding_provider' => 'The active funding configuration has no usable credentials.']);
        }

        $banks = $config->management_mode === 'affiliate_managed'
            ? $config->banks->filter(fn ($bank) => $bank->parentBank?->active && $bank->parentBank?->generation_enabled)->values()
            : $config->parentFundingProvider->banks;

        return [
            'affiliate' => $affiliate,
            'parent' => $affiliate->parentBusiness,
            'provider' => $config->parentFundingProvider->fundingProvider,
            'parent_provider' => $config->parentFundingProvider,
            'affiliate_config' => $config,
            'management_mode' => $config->management_mode,
            'credentials' => $credentials,
            'banks' => $banks,
            'bank_codes' => $banks->map(fn ($bank) => $config->management_mode === 'affiliate_managed' ? $bank->parentBank->bank_code : $bank->bank_code)->values()->all(),
            'settings' => $config->settings ?? [],
        ];
    }
}
