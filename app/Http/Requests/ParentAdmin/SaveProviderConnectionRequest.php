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
            'settings.request_parameters.*.key' => ['required', 'string', 'max:255', 'distinct'],
            'settings.request_parameters.*.type' => ['required', Rule::in(['runtime', 'credential', 'literal'])],
            'settings.request_parameters.*.value' => ['present', 'nullable', 'string', 'max:4096'],
            'settings.request_headers' => ['nullable', 'array'],
            'settings.request_headers.*.key' => ['required', 'string', 'max:255', 'distinct'],
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

            if (collect($settings['endpoints'] ?? [])->filter()->isEmpty() && blank($this->input('base_url'))) {
                $validator->errors()->add('settings.endpoints', 'Provide a base URL or at least one service endpoint.');
            }
            if (! in_array($settings['http_method'] ?? null, $methods, true)) {
                $validator->errors()->add('settings.http_method', 'The selected adapter does not support this HTTP method.');
            }
            foreach ($settings['endpoints'] ?? [] as $service => $endpoint) {
                if (filled($endpoint) && ! in_array($products->normalize($service), $services, true)) {
                    $validator->errors()->add("settings.endpoints.{$service}", 'The selected adapter does not support this service.');
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
}
