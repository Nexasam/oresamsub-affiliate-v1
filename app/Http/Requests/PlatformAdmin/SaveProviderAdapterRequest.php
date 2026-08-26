<?php

namespace App\Http\Requests\PlatformAdmin;

use App\Support\ProviderProductRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveProviderAdapterRequest extends FormRequest
{
    public const METHODS = ['GET', 'POST'];

    public const CREDENTIAL_FIELDS = ['api_public_key', 'api_secret_key', 'api_password', 'api_token', 'business_id', 'contract_code', 'username', 'account_id'];

    protected function prepareForValidation(): void
    {
        $capabilities = $this->input('capabilities', []);

        $products = app(ProviderProductRegistry::class);

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'slug' => Str::slug((string) $this->input('slug')),
            'adapter_key' => Str::of((string) ($this->input('adapter_key') ?: $this->input('adapter')))->trim()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString(),
            'capabilities' => [
                'services' => array_values(array_map(
                    fn ($service) => $products->normalize((string) $service),
                    $capabilities['services'] ?? []
                )),
                'methods' => array_values(array_map('strtoupper', $capabilities['methods'] ?? [])),
                'credential_fields' => array_values($capabilities['credential_fields'] ?? []),
            ],
        ]);
    }

    public function rules(): array
    {
        $adapter = $this->route('providerAdapter');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash:ascii', 'max:255', Rule::unique('provider_adapters', 'slug')->ignore($adapter)],
            'adapter_key' => ['required', 'regex:/^[a-z][a-z0-9_]*$/', 'max:255', Rule::unique('provider_adapters', 'adapter_key')->ignore($adapter)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'capabilities' => ['required', 'array:services,methods,credential_fields'],
            'capabilities.services' => ['required', 'array', 'min:1', 'max:100'],
            'capabilities.services.*' => ['required', 'distinct', Rule::in(app(ProviderProductRegistry::class)->slugs(includeLegacy: true))],
            'capabilities.methods' => ['required', 'array', 'min:1', 'max:2'],
            'capabilities.methods.*' => ['required', 'distinct', Rule::in(self::METHODS)],
            'capabilities.credential_fields' => ['present', 'array', 'max:8'],
            'capabilities.credential_fields.*' => ['required', 'distinct', Rule::in(self::CREDENTIAL_FIELDS)],
            'settings' => ['nullable', 'array'],
        ];
    }
}
