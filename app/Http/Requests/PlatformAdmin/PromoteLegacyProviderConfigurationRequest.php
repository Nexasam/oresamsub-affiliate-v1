<?php

namespace App\Http\Requests\PlatformAdmin;

use Illuminate\Foundation\Http\FormRequest;

class PromoteLegacyProviderConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform_admin') !== null;
    }

    public function rules(): array
    {
        return ['promote_to_adapter' => ['required', 'boolean']];
    }
}
