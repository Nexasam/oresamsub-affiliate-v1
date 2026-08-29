<?php

namespace App\Http\Requests\ParentAdmin;

use Illuminate\Foundation\Http\FormRequest;

class BulkSwitchProductPlanRoutesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('parent_admin');
    }

    public function rules(): array
    {
        return [
            'parent_provider_connection_id' => ['required', 'integer'],
            'plans' => ['required', 'array', 'min:1', 'max:2000'],
            'plans.*.product_plan_id' => ['required', 'integer', 'distinct'],
            'plans.*.provider_plan_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
