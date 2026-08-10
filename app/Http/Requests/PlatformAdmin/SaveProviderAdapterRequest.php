<?php

namespace App\Http\Requests\PlatformAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveProviderAdapterRequest extends FormRequest
{
    public const SERVICES = ['data', 'airtime', 'cable', 'electricity'];

    public const METHODS = ['GET', 'POST'];

    public const CREDENTIAL_FIELDS = ['api_public_key', 'api_secret_key', 'api_password'];

    protected function prepareForValidation(): void
    {
        $capabilities = $this->input('capabilities', []);

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'slug' => Str::slug((string) $this->input('slug')),
            'adapter' => Str::of((string) $this->input('adapter'))->trim()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString(),
            'capabilities' => [
                'services' => array_values($capabilities['services'] ?? []),
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
            'slug' => ['required', 'alpha_dash:ascii', 'max:255', Rule::unique('provider_connections', 'slug')->ignore($adapter)],
            'adapter' => ['required', 'regex:/^[a-z][a-z0-9_]*$/', 'max:255', Rule::unique('provider_connections', 'adapter')->ignore($adapter)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'capabilities' => ['required', 'array:services,methods,credential_fields'],
            'capabilities.services' => ['required', 'array', 'min:1', 'max:4'],
            'capabilities.services.*' => ['required', 'distinct', Rule::in(self::SERVICES)],
            'capabilities.methods' => ['required', 'array', 'min:1', 'max:2'],
            'capabilities.methods.*' => ['required', 'distinct', Rule::in(self::METHODS)],
            'capabilities.credential_fields' => ['present', 'array', 'max:3'],
            'capabilities.credential_fields.*' => ['required', 'distinct', Rule::in(self::CREDENTIAL_FIELDS)],
        ];
    }
}
