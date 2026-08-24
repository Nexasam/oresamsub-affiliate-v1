<?php

namespace App\Http\Requests\ParentAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveTransactionReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('parent_admin') !== null;
    }

    public function rules(): array
    {
        return [
            'outcome' => ['required', Rule::in(['successful', 'failed'])],
            'provider_confirmed' => ['accepted'],
            'provider_reference' => ['nullable', 'string', 'max:255'],
            'note' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
