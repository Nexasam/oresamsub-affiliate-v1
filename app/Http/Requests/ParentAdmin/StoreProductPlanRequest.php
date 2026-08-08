<?php

namespace App\Http\Requests\ParentAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('parent_admin') !== null;
    }

    public function rules(): array
    {
        return [
            'product_plan_name' => ['required', 'string', 'max:255'],
            'product_plan_category_id' => ['required', 'integer', Rule::exists('product_plan_categories', 'id')],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'profit_category' => ['required', Rule::in(['flat', 'percent'])],
            'visibility' => ['sometimes', 'boolean'],
            'affiliate_visibility' => ['sometimes', 'boolean'],
            'public_visibility' => ['sometimes', 'boolean'],
        ];
    }
}
