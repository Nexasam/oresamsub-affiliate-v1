<?php

namespace App\Services\Funding;

use App\Models\Affiliate;
use App\Models\AffiliateSettlementVirtualAccount;
use App\Models\ParentFundingProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class SettlementVirtualAccountService
{
    public function generate(Affiliate $affiliate, ParentFundingProvider $parentProvider): array
    {
        if (! config('parent_businesses.features.multi_parent_funding', false)) {
            return ['status' => -1, 'message' => 'Multi-parent funding is unavailable.'];
        }
        if ((int) $affiliate->parent_business_id !== (int) $parentProvider->parent_business_id || ! $parentProvider->active || ! $parentProvider->generation_enabled) {
            throw ValidationException::withMessages(['provider' => 'This funding provider is not enabled for the affiliate parent.']);
        }

        $parentProvider->loadMissing(['fundingProvider', 'banks']);
        $banks = $parentProvider->banks->where('active', true)->where('generation_enabled', true);
        $missing = $banks->reject(fn ($bank) => AffiliateSettlementVirtualAccount::query()
            ->where('parent_funding_provider_id', $parentProvider->id)->where('affiliate_id', $affiliate->id)
            ->where('bank_code', $bank->bank_code)->exists());
        if ($missing->isEmpty()) return ['status' => 1, 'message' => 'Settlement virtual accounts already generated.'];

        $credentials = $parentProvider->credentials ?? [];
        $count = match ($parentProvider->fundingProvider->adapter_key) {
            'securewaveng' => $this->securewave($affiliate, $parentProvider, $credentials, $missing->pluck('bank_code')->all()),
            'xixapay' => $this->xixapay($affiliate, $parentProvider, $credentials, $missing->pluck('bank_code')->all()),
            default => throw new \RuntimeException('The funding-provider adapter cannot generate settlement accounts.'),
        };

        return ['status' => 1, 'message' => $count ? 'Settlement virtual account generated.' : 'No account was generated.'];
    }

    private function securewave(Affiliate $affiliate, ParentFundingProvider $provider, array $credentials, array $bankCodes): int
    {
        $response = Http::acceptJson()->asJson()->timeout(30)
            ->withToken($this->credential($credentials, 'api_secret_key'))
            ->withHeaders(['x-api-key' => $this->credential($credentials, 'api_public_key')])
            ->post(data_get($provider, 'fundingProvider.settings.virtual_account_url', 'https://securewaveng.com/api/virtual_accounts/generate'), [
                'email' => $affiliate->contact_email, 'first_name' => $affiliate->name, 'last_name' => 'Business',
                'phone_number' => $affiliate->contact_phone, 'bank_code' => $bankCodes,
                'business_id' => $this->credential($credentials, 'business_id', ['contract_code']),
                'account_type' => 'static', 'id_type' => 'bvn', 'id_number' => $credentials['biz_bvn'] ?? null,
                'metadata' => ['wallet_purpose' => 'settlement', 'affiliate_id' => $affiliate->id],
            ]);
        $response->throw();
        if ($response->json('status') !== true) throw new \RuntimeException((string) ($response->json('message') ?: 'SecurewaveNG rejected settlement-account generation.'));

        return $this->storeAccounts($affiliate, $provider, collect($response->json('data', []))->map(fn ($account) => [
            'bank_name' => $account['account_bank'] ?? null, 'bank_code' => $account['bank_code'] ?? null,
            'account_name' => $account['account_name'] ?? $affiliate->name, 'account_number' => $account['account_number'] ?? null,
            'account_reference' => $account['account_reference'] ?? null, 'provider_metadata' => $account,
        ])->all());
    }

    private function xixapay(Affiliate $affiliate, ParentFundingProvider $provider, array $credentials, array $bankCodes): int
    {
        $accounts = [];
        foreach ($bankCodes as $bankCode) {
            $response = Http::acceptJson()->asJson()->timeout(30)
                ->withToken($this->credential($credentials, 'api_secret_key'))
                ->withHeaders(['api-key' => $this->credential($credentials, 'api_public_key')])
                ->post(data_get($provider, 'fundingProvider.settings.virtual_account_url', 'https://api.xixapay.com/api/v1/createVirtualAccount'), [
                    'email' => $affiliate->contact_email, 'name' => $affiliate->name, 'phoneNumber' => $affiliate->contact_phone,
                    'bankCode' => [$bankCode], 'businessId' => $this->credential($credentials, 'contract_code', ['business_id']),
                    'accountType' => 'static', 'id_type' => 'bvn', 'id_number' => $credentials['biz_bvn'] ?? null,
                ]);
            $response->throw();
            if ($response->json('status') !== 'success') throw new \RuntimeException((string) ($response->json('message') ?: 'Xixapay rejected settlement-account generation.'));
            foreach ($response->json('bankAccounts', []) as $account) $accounts[] = [
                'bank_name' => $account['bankName'] ?? null, 'bank_code' => $account['bankCode'] ?? $bankCode,
                'account_name' => $account['accountName'] ?? $affiliate->name, 'account_number' => $account['accountNumber'] ?? null,
                'account_reference' => $account['Reserved_Account_Id'] ?? null, 'provider_metadata' => $account,
            ];
        }
        return $this->storeAccounts($affiliate, $provider, $accounts);
    }

    private function storeAccounts(Affiliate $affiliate, ParentFundingProvider $provider, array $accounts): int
    {
        $count = 0;
        foreach ($accounts as $account) {
            if (! filled($account['account_number']) || ! filled($account['bank_code'])) continue;
            AffiliateSettlementVirtualAccount::updateOrCreate([
                'parent_funding_provider_id' => $provider->id, 'affiliate_id' => $affiliate->id, 'bank_code' => $account['bank_code'],
            ], array_merge($account, ['parent_business_id' => $affiliate->parent_business_id, 'wallet_purpose' => 'settlement', 'status' => 'active']));
            $count++;
        }
        return $count;
    }

    private function credential(array $credentials, string $key, array $aliases = []): string
    {
        $value = (string) ($credentials[$key] ?? '');
        foreach ($aliases as $alias) if ($value === '') $value = (string) ($credentials[$alias] ?? '');
        if ($value === '') throw new \RuntimeException("The {$key} credential is missing.");
        return $value;
    }
}
