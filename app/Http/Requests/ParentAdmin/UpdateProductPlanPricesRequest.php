<?php

namespace App\Http\Requests\ParentAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductPlanPricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('parent_admin') !== null;
    }

    public function rules(): array
    {
        return [
            'prices' => ['required', 'array', 'min:1', 'max:6'],
            'prices.*.parent_reseller_level_id' => ['required', 'integer', 'distinct'],
            'prices.*.selling_price' => ['required', 'numeric', 'min:0'],
            'prices.*.max_profit' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
