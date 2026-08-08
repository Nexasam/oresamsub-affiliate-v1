<?php

namespace App\Http\Requests\ParentAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('parent_admin') !== null;
    }

    public function rules(): array
    {
        return [
            'product_plan_name' => ['sometimes', 'required', 'string', 'max:255'],
            'product_plan_category_id' => ['sometimes', 'required', 'integer', Rule::exists('product_plan_categories', 'id')],
            'cost_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'profit_category' => ['sometimes', 'required', Rule::in(['flat', 'percent'])],
            'visibility' => ['sometimes', 'boolean'],
            'affiliate_visibility' => ['sometimes', 'boolean'],
            'public_visibility' => ['sometimes', 'boolean'],
        ];
    }
}
