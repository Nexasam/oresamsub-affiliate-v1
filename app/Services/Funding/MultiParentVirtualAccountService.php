<?php

namespace App\Services\Funding;

use App\Models\AffiliateFundingProviderConfig;
use App\Models\User;
use App\Models\UserVirtualAccount;
use Illuminate\Support\Facades\Http;
use Throwable;

class MultiParentVirtualAccountService
{
    public function __construct(private readonly FundingConfigurationResolver $resolver) {}

    public function generateForUser(User $user): array
    {
        if (! config('parent_businesses.features.multi_parent_funding', false) || ! $user->affiliate) {
            return ['status' => -1, 'handled' => false, 'message' => 'Multi-parent funding is unavailable.'];
        }

        $configs = AffiliateFundingProviderConfig::query()
            ->where('affiliate_id', $user->affiliate_id)->where('active', true)->where('generation_enabled', true)
            ->whereHas('parentFundingProvider', fn ($query) => $query->where('active', true)->where('generation_enabled', true))
            ->with('parentFundingProvider.fundingProvider')->get();
        if ($configs->isEmpty()) {
            return ['status' => -1, 'handled' => false, 'message' => 'No normalized funding provider is configured.'];
        }

        $generated = 0;
        $errors = [];
        foreach ($configs as $config) {
            $slug = $config->parentFundingProvider->fundingProvider->slug;
            try {
                $resolved = $this->resolver->resolveForGeneration($user->affiliate, $slug);
                $generated += $this->generate($user, $resolved);
            } catch (Throwable $exception) {
                report($exception);
                $errors[] = "{$slug}: {$exception->getMessage()}";
            }
        }

        return [
            'status' => $errors === [] ? 1 : -1,
            'handled' => true,
            'message' => $errors === [] ? ($generated > 0 ? 'Virtual accounts generated.' : 'Virtual accounts already generated.') : implode(' ', $errors),
        ];
    }

    private function generate(User $user, array $resolved): int
    {
        $missingCodes = collect($resolved['bank_codes'])->reject(fn ($code) => UserVirtualAccount::withoutGlobalScope('affiliate')
            ->where('affiliate_funding_provider_config_id', $resolved['affiliate_config']->id)
            ->where('user_id', $user->id)->where('bank_code', $code)->exists())->values()->all();
        if ($missingCodes === []) {
            return 0;
        }

        return match ($resolved['provider']->adapter_key) {
            'xixapay' => $this->generateXixapay($user, $resolved, $missingCodes),
            'securewaveng' => $this->generateSecurewave($user, $resolved, $missingCodes),
            default => throw new \RuntimeException('The funding-provider adapter cannot generate virtual accounts.'),
        };
    }

    private function generateXixapay(User $user, array $resolved, array $bankCodes): int
    {
        $count = 0;
        foreach ($bankCodes as $bankCode) {
            $response = Http::acceptJson()->asJson()->timeout(30)
                ->withToken($this->credential($resolved, 'api_secret_key'))
                ->withHeaders(['api-key' => $this->credential($resolved, 'api_public_key')])
                ->post(data_get($resolved, 'provider.settings.virtual_account_url', 'https://api.xixapay.com/api/v1/createVirtualAccount'), [
                    'email' => $user->email,
                    'name' => trim("{$user->first_name} {$user->last_name}"),
                    'phoneNumber' => $user->phone_number,
                    'bankCode' => [$bankCode],
                    'businessId' => $this->credential($resolved, 'contract_code'),
                    'accountType' => 'static',
                    'id_type' => 'bvn',
                    'id_number' => $resolved['credentials']['biz_bvn'] ?? $user->bvn,
                ]);
            $response->throw();
            if ($response->json('status') !== 'success') {
                throw new \RuntimeException((string) ($response->json('message') ?: 'Xixapay rejected virtual-account generation.'));
            }
            foreach ($response->json('bankAccounts', []) as $account) {
                $this->store($user, $resolved, [
                    'bank_name' => $account['bankName'] ?? null, 'bank_code' => $account['bankCode'] ?? $bankCode,
                    'account_name' => $account['accountName'] ?? null, 'account_number' => $account['accountNumber'] ?? null,
                    'account_reference' => $account['Reserved_Account_Id'] ?? null, 'response_status' => 'success',
                ]);
                $count++;
            }
        }

        return $count;
    }

    private function generateSecurewave(User $user, array $resolved, array $bankCodes): int
    {
        $response = Http::acceptJson()->asJson()->timeout(30)
            ->withToken($this->credential($resolved, 'api_secret_key'))
            ->withHeaders(['x-api-key' => $this->credential($resolved, 'api_public_key')])
            ->post(data_get($resolved, 'provider.settings.virtual_account_url', 'https://securewaveng.com/api/virtual_accounts/generate'), [
                'email' => $user->email, 'first_name' => $user->first_name, 'last_name' => $user->last_name,
                'phone_number' => $user->phone_number, 'bank_code' => $bankCodes,
                'business_id' => $this->credential($resolved, 'contract_code'), 'account_type' => 'static',
                'id_type' => 'bvn', 'id_number' => $resolved['credentials']['biz_bvn'] ?? $user->bvn,
            ]);
        $response->throw();
        if ($response->json('status') !== true) {
            throw new \RuntimeException((string) ($response->json('message') ?: 'SecurewaveNG rejected virtual-account generation.'));
        }

        $count = 0;
        foreach ($response->json('data', []) as $account) {
            if ((int) ($account['status'] ?? 0) !== 1) {
                continue;
            }
            $this->store($user, $resolved, [
                'bank_name' => $account['account_bank'] ?? null, 'bank_code' => $account['bank_code'] ?? null,
                'account_name' => $account['account_name'] ?? null, 'account_email' => $account['account_email'] ?? $user->email,
                'account_number' => $account['account_number'] ?? null, 'account_reference' => $account['account_reference'] ?? null,
                'response_status' => (string) ($account['status'] ?? 1),
            ]);
            $count++;
        }

        return $count;
    }

    private function store(User $user, array $resolved, array $account): void
    {
        UserVirtualAccount::withoutGlobalScope('affiliate')->updateOrCreate([
            'affiliate_funding_provider_config_id' => $resolved['affiliate_config']->id,
            'user_id' => $user->id,
            'bank_code' => $account['bank_code'],
        ], array_merge($account, [
            'affiliate_id' => $user->affiliate_id,
            'parent_business_id' => $resolved['parent']->id,
            'funding_option_id' => null,
            'parent_funding_provider_id' => $resolved['parent_provider']->id,
            'funding_slug' => $resolved['provider']->slug,
            'account_email' => $account['account_email'] ?? $user->email,
            'bvn' => $resolved['credentials']['biz_bvn'] ?? $user->bvn,
        ]));
    }

    private function credential(array $resolved, string $key): string
    {
        $value = (string) ($resolved['credentials'][$key] ?? '');
        if ($value === '') {
            throw new \RuntimeException("The {$key} credential is missing.");
        }

        return $value;
    }
}
