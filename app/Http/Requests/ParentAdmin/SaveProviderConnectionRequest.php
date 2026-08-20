<?php

namespace App\Http\Requests\ParentAdmin;

use App\Models\ProviderConnection;
use App\Support\ProviderProductRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveProviderConnectionRequest extends FormRequest
{
    public const RUNTIME_FIELDS = [
        'phone_number', 'network', 'plan', 'amount', 'email', 'user', 'ported_number', 'reference', 'action',
        'meter_number', 'meter_type', 'smartcard_number', 'customer_name', 'quantity', 'exam_type', 'service_provider',
    ];

    public const CREDENTIAL_FIELDS = ['api_public_key', 'api_secret_key', 'api_password'];

    public function authorize(): bool
    {
        return $this->user('parent_admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        $settings = $this->input('settings', []);
        $productConfigs = $settings['product_configs'] ?? [];
        $products = app(ProviderProductRegistry::class);

        foreach ($productConfigs as $service => &$productConfig) {
            $normalizedService = $products->normalize((string) $service);
            $endpoint = $settings['endpoints'][$service]
                ?? $settings['endpoints'][$normalizedService]
                ?? null;

            if (blank($endpoint)) {
                unset($productConfigs[$service]);

                continue;
            }

            if (! in_array($normalizedService, ['cable_subscription', 'utility_bills'], true)) {
                unset($productConfig['validation']);
            }
        }
        unset($productConfig);

        $settings['product_configs'] = $productConfigs;
        $this->merge(['settings' => $settings]);
    }

    public function rules(): array
    {
        $existingProviderId = $this->route('connection')?->provider_connection_id;

        return [
            'provider_connection_id' => ['required', 'integer', Rule::exists('provider_connections', 'id')->where(
                fn ($query) => $query->where('status', 'active')->when(
                    $existingProviderId,
                    fn ($query) => $query->orWhere('id', $existingProviderId)
                )
            )],
            'name' => ['required', 'string', 'max:255'],
            'base_url' => ['nullable', 'url:http,https', 'max:2048'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_primary' => ['required', 'boolean'],
            'credentials' => ['nullable', 'array'],
            'credentials.api_public_key' => ['nullable', 'string', 'max:4096'],
            'credentials.api_secret_key' => ['nullable', 'string', 'max:4096'],
            'credentials.api_password' => ['nullable', 'string', 'max:4096'],
            'settings' => ['required', 'array'],
            'settings.http_method' => ['required', Rule::in(['GET', 'POST'])],
            'settings.timeout_seconds' => ['required', 'integer', 'between:5,120'],
            'settings.endpoints' => ['required', 'array'],
            'settings.endpoints.*' => ['nullable', 'url:http,https', 'max:2048'],
            'settings.request_parameters' => ['required', 'array', 'min:1'],
            'settings.request_parameters.*.key' => ['required', 'string', 'max:255'],
            'settings.request_parameters.*.type' => ['required', Rule::in(['runtime', 'credential', 'literal'])],
            'settings.request_parameters.*.value' => ['present', 'nullable', 'string', 'max:4096'],
            'settings.request_headers' => ['nullable', 'array'],
            'settings.request_headers.*.key' => ['required', 'string', 'max:255'],
            'settings.request_headers.*.type' => ['required', Rule::in(['runtime', 'credential', 'literal'])],
            'settings.request_headers.*.value' => ['present', 'nullable', 'string', 'max:4096'],
            'settings.request_headers.*.prefix' => ['nullable', 'string', 'max:255'],
            'settings.request_headers.*.suffix' => ['nullable', 'string', 'max:255'],
            'settings.network_mapping' => ['nullable', 'array'],
            'settings.network_mapping.*' => ['nullable', 'string', 'max:255'],
            'settings.success_conditions' => ['required', 'array', 'min:1'],
            'settings.success_conditions.*.key' => ['required', 'string', 'max:255'],
            'settings.success_conditions.*.value' => ['present'],
            'settings.success_message_path' => ['required', 'string', 'max:255'],
            'settings.failure_message_path' => ['required', 'string', 'max:255'],
            'settings.expected_success_code' => ['nullable', 'integer', 'between:100,599'],
            'settings.expected_failure_code' => ['nullable', 'integer', 'between:100,599'],
            'settings.bank_name' => ['nullable', 'string', 'max:255'],
            'settings.bank_accounts' => ['nullable', 'string', 'max:2000'],
            'settings.support_url' => ['nullable', 'url:http,https', 'max:2048'],
            'settings.product_configs' => ['nullable', 'array'],
            'settings.product_configs.*' => ['array'],
            'settings.product_configs.*.request_parameters' => ['required', 'array', 'min:1'],
            'settings.product_configs.*.request_parameters.*.key' => ['required', 'string', 'max:255'],
            'settings.product_configs.*.request_parameters.*.type' => ['required', Rule::in(['runtime', 'credential', 'literal'])],
            'settings.product_configs.*.request_parameters.*.value' => ['present', 'nullable', 'string', 'max:4096'],
            'settings.product_configs.*.request_headers' => ['nullable', 'array'],
            'settings.product_configs.*.request_headers.*.key' => ['required', 'string', 'max:255'],
            'settings.product_configs.*.request_headers.*.type' => ['required', Rule::in(['runtime', 'credential', 'literal'])],
            'settings.product_configs.*.request_headers.*.value' => ['present', 'nullable', 'string', 'max:4096'],
            'settings.product_configs.*.request_headers.*.prefix' => ['nullable', 'string', 'max:255'],
            'settings.product_configs.*.request_headers.*.suffix' => ['nullable', 'string', 'max:255'],
            'settings.product_configs.*.network_mapping' => ['nullable', 'array'],
            'settings.product_configs.*.network_mapping.*' => ['nullable', 'string', 'max:255'],
            'settings.product_configs.*.success_conditions' => ['required', 'array', 'min:1'],
            'settings.product_configs.*.success_conditions.*.key' => ['required', 'string', 'max:255'],
            'settings.product_configs.*.success_conditions.*.value' => ['present'],
            'settings.product_configs.*.success_message_path' => ['required', 'string', 'max:255'],
            'settings.product_configs.*.failure_message_path' => ['required', 'string', 'max:255'],
            'settings.product_configs.*.actual_charge_path' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'settings.product_configs.*.expected_success_code' => ['nullable', 'integer', 'between:100,599'],
            'settings.product_configs.*.expected_failure_code' => ['nullable', 'integer', 'between:100,599'],
            'settings.product_configs.*.validation' => ['nullable', 'array'],
            'settings.product_configs.*.validation.endpoint' => ['required_with:settings.product_configs.*.validation', 'url:http,https', 'max:2048'],
            'settings.product_configs.*.validation.http_method' => ['required_with:settings.product_configs.*.validation', Rule::in(['GET', 'POST'])],
            'settings.product_configs.*.validation.request_parameters' => ['required_with:settings.product_configs.*.validation', 'array', 'min:1'],
            'settings.product_configs.*.validation.request_parameters.*.key' => ['required', 'string', 'max:255'],
            'settings.product_configs.*.validation.request_parameters.*.type' => ['required', Rule::in(['runtime', 'credential', 'literal'])],
            'settings.product_configs.*.validation.request_parameters.*.value' => ['present', 'nullable', 'string', 'max:4096'],
            'settings.product_configs.*.validation.request_headers' => ['nullable', 'array'],
            'settings.product_configs.*.validation.request_headers.*.key' => ['required', 'string', 'max:255'],
            'settings.product_configs.*.validation.request_headers.*.type' => ['required', Rule::in(['runtime', 'credential', 'literal'])],
            'settings.product_configs.*.validation.request_headers.*.value' => ['present', 'nullable', 'string', 'max:4096'],
            'settings.product_configs.*.validation.request_headers.*.prefix' => ['nullable', 'string', 'max:255'],
            'settings.product_configs.*.validation.request_headers.*.suffix' => ['nullable', 'string', 'max:255'],
            'settings.product_configs.*.validation.success_conditions' => ['required_with:settings.product_configs.*.validation', 'array', 'min:1'],
            'settings.product_configs.*.validation.success_conditions.*.key' => ['required', 'string', 'max:255'],
            'settings.product_configs.*.validation.success_conditions.*.value' => ['present'],
            'settings.product_configs.*.validation.success_message_path' => ['required_with:settings.product_configs.*.validation', 'string', 'max:255'],
            'settings.product_configs.*.validation.failure_message_path' => ['required_with:settings.product_configs.*.validation', 'string', 'max:255'],
            'settings.product_configs.*.validation.customer_name_path' => ['required_with:settings.product_configs.*.validation', 'string', 'max:255'],
            'settings.product_configs.*.validation.customer_address_path' => ['nullable', 'string', 'max:255'],
            'settings.product_configs.*.validation.expected_success_code' => ['nullable', 'integer', 'between:100,599'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $settings = $this->input('settings', []);
            $products = app(ProviderProductRegistry::class);
            $adapter = ProviderConnection::find($this->integer('provider_connection_id'));
            $capabilities = $adapter?->capabilities ?? [];
            $services = collect($capabilities['services'] ?? $products->slugs(includeLegacy: true))
                ->map(fn ($service) => $products->normalize($service))->all();
            $methods = $capabilities['methods'] ?? ['GET', 'POST'];
            $credentialFields = $capabilities['credential_fields'] ?? self::CREDENTIAL_FIELDS;

            $this->validateUniqueKeys($validator, $settings['request_parameters'] ?? [], 'settings.request_parameters', false);
            $this->validateUniqueKeys($validator, $settings['request_headers'] ?? [], 'settings.request_headers', true);

            if (collect($settings['endpoints'] ?? [])->filter(fn ($endpoint) => filled($endpoint))->isEmpty()) {
                $validator->errors()->add('settings.endpoints', 'Configure at least one service endpoint.');
            }
            if (! in_array($settings['http_method'] ?? null, $methods, true)) {
                $validator->errors()->add('settings.http_method', 'The selected adapter does not support this HTTP method.');
            }
            foreach ($settings['endpoints'] ?? [] as $service => $endpoint) {
                if (filled($endpoint) && ! in_array($products->normalize($service), $services, true)) {
                    $validator->errors()->add("settings.endpoints.{$service}", 'The selected adapter does not support this service.');
                }
            }
            foreach ($settings['product_configs'] ?? [] as $service => $productConfig) {
                $normalizedService = $products->normalize($service);
                $path = "settings.product_configs.{$service}";
                if (! in_array($normalizedService, $services, true)) {
                    $validator->errors()->add($path, 'The selected adapter does not support this service.');

                    continue;
                }
                $this->validateUniqueKeys($validator, $productConfig['request_parameters'] ?? [], "{$path}.request_parameters", false);
                $this->validateUniqueKeys($validator, $productConfig['request_headers'] ?? [], "{$path}.request_headers", true);
                $this->validateMappings(
                    $validator,
                    array_merge($productConfig['request_parameters'] ?? [], $productConfig['request_headers'] ?? []),
                    $credentialFields,
                    $path
                );
                foreach ($productConfig['request_headers'] ?? [] as $header) {
                    if (strtolower($header['key'] ?? '') === 'authorization' && ($header['type'] ?? null) === 'literal') {
                        $validator->errors()->add("{$path}.request_headers", 'Authorization headers must use a credential placeholder.');
                    }
                }
                if (is_array($productConfig['validation'] ?? null)) {
                    $validation = $productConfig['validation'];
                    $validationPath = "{$path}.validation";
                    $this->validateUniqueKeys($validator, $validation['request_parameters'] ?? [], "{$validationPath}.request_parameters", false);
                    $this->validateUniqueKeys($validator, $validation['request_headers'] ?? [], "{$validationPath}.request_headers", true);
                    $this->validateMappings(
                        $validator,
                        array_merge($validation['request_parameters'] ?? [], $validation['request_headers'] ?? []),
                        $credentialFields,
                        $validationPath,
                    );
                    foreach ($validation['request_headers'] ?? [] as $header) {
                        if (strtolower($header['key'] ?? '') === 'authorization' && ($header['type'] ?? null) === 'literal') {
                            $validator->errors()->add("{$validationPath}.request_headers", 'Authorization headers must use a credential placeholder.');
                        }
                    }
                }
            }
            foreach ($this->input('credentials', []) ?? [] as $field => $value) {
                if (filled($value) && ! in_array($field, $credentialFields, true)) {
                    $validator->errors()->add("credentials.{$field}", 'The selected adapter does not permit this credential field.');
                }
            }
            foreach (array_merge($settings['request_parameters'] ?? [], $settings['request_headers'] ?? []) as $mapping) {
                if (($mapping['type'] ?? null) === 'runtime' && ! in_array($mapping['value'] ?? null, self::RUNTIME_FIELDS, true)) {
                    $validator->errors()->add('settings.request_parameters', 'A runtime mapping contains an unsupported internal field.');
                }
                if (($mapping['type'] ?? null) === 'credential' && ! in_array($mapping['value'] ?? null, self::CREDENTIAL_FIELDS, true)) {
                    $validator->errors()->add('settings.request_parameters', 'A credential mapping contains an unsupported credential field.');
                }
                if (($mapping['type'] ?? null) === 'credential' && ! in_array($mapping['value'] ?? null, $credentialFields, true)) {
                    $validator->errors()->add('settings.request_headers', 'A credential mapping is not permitted by the selected adapter.');
                }
            }
            foreach ($settings['request_headers'] ?? [] as $header) {
                if (strtolower($header['key'] ?? '') === 'authorization' && ($header['type'] ?? null) === 'literal') {
                    $validator->errors()->add('settings.request_headers', 'Authorization headers must use a credential placeholder.');
                }
            }
        }];
    }

    private function validateMappings(Validator $validator, array $mappings, array $credentialFields, string $path): void
    {
        foreach ($mappings as $mapping) {
            if (($mapping['type'] ?? null) === 'runtime' && ! in_array($mapping['value'] ?? null, self::RUNTIME_FIELDS, true)) {
                $validator->errors()->add("{$path}.request_parameters", 'A runtime mapping contains an unsupported internal field.');
            }
            if (($mapping['type'] ?? null) === 'credential' && ! in_array($mapping['value'] ?? null, self::CREDENTIAL_FIELDS, true)) {
                $validator->errors()->add("{$path}.request_parameters", 'A credential mapping contains an unsupported credential field.');
            }
            if (($mapping['type'] ?? null) === 'credential' && ! in_array($mapping['value'] ?? null, $credentialFields, true)) {
                $validator->errors()->add("{$path}.request_headers", 'A credential mapping is not permitted by the selected adapter.');
            }
        }
    }

    private function validateUniqueKeys(Validator $validator, array $mappings, string $path, bool $caseInsensitive): void
    {
        $keys = collect($mappings)->pluck('key')->filter(fn ($key) => filled($key));
        if ($caseInsensitive) {
            $keys = $keys->map(fn ($key) => strtolower((string) $key));
        }

        if ($keys->duplicates()->isNotEmpty()) {
            $validator->errors()->add($path, 'Each key may appear only once within a product configuration.');
        }
    }
}
