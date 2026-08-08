<?php

namespace App\Http\Requests\ParentAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDefaultProfitRulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('parent_admin') !== null;
    }

    public function rules(): array
    {
        return [
            'rules' => ['required', 'array', 'min:1'],
            'rules.*.parent_reseller_level_id' => ['required', 'integer'],
            'rules.*.product_id' => ['required', 'integer'],
            'rules.*.calculation_type' => ['required', Rule::in(['flat', 'percent_discount'])],
            'rules.*.value' => ['required', 'numeric', 'min:0'],
        ];
    }
}
