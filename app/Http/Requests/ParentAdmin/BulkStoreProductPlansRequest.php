<?php

namespace App\Http\Requests\ParentAdmin;

use App\Http\Requests\ParentAdmin\Concerns\ValidatesParentProductPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BulkStoreProductPlansRequest extends FormRequest
{
    use ValidatesParentProductPlan;

    public function authorize(): bool
    {
        return $this->user('parent_admin') !== null;
    }

    public function rules(): array
    {
        return [
            'plans' => ['required', 'array', 'min:1', 'max:100'],
            ...$this->productPlanRules('plans.*.'),
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ($this->input('plans', []) as $index => $plan) {
                $this->validateProductPlanPayload($validator, $plan, "plans.{$index}");
            }
        }];
    }
}
