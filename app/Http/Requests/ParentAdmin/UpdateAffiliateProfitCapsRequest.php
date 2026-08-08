<?php

namespace App\Http\Requests\ParentAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAffiliateProfitCapsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('parent_admin') !== null;
    }

    public function rules(): array
    {
        return [
            'caps' => ['required', 'array', 'min:1', 'max:24'],
            'caps.*.product_id' => ['required', 'integer'],
            'caps.*.customer_level' => ['required', 'integer', 'between:1,6'],
            'caps.*.calculation_type' => ['required', Rule::in(['flat', 'percent'])],
            'caps.*.max_value' => ['required', 'numeric', 'min:0'],
        ];
    }
}
