<?php

namespace App\Http\Requests\PlatformAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewParentProviderConnectionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['nullable', 'required_if:action,reject', 'string', 'min:10', 'max:2000'],
        ];
    }
}
