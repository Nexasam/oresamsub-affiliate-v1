<?php

namespace App\Http\Requests\PlatformAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProviderConnectionCatalogueRequest extends FormRequest
{
    public function rules(): array
    {
        $connection = $this->route('providerConnection');

        return [
            'provider_adapter_id' => ['required', Rule::exists('provider_adapters', 'id')->where('status', 'active')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash:ascii', 'max:255', Rule::unique('provider_connections', 'slug')->ignore($connection)],
            'base_url' => ['nullable', 'url:http,https', 'max:2048'],
            'website_url' => ['nullable', 'url:http,https', 'max:2048'],
            'documentation_url' => ['nullable', 'url:http,https', 'max:2048'],
            'settings_overrides' => ['nullable', 'array'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
