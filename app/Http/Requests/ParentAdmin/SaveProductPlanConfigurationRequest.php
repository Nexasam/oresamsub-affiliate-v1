<?php

namespace App\Http\Requests\ParentAdmin;

use App\Http\Requests\ParentAdmin\Concerns\ValidatesParentProductPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveProductPlanConfigurationRequest extends FormRequest
{
    use ValidatesParentProductPlan;

    public function authorize(): bool
    {
        return $this->user('parent_admin') !== null;
    }

    public function rules(): array
    {
        return $this->productPlanRules();
    }

    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateProductPlanPayload($validator, $this->all())];
    }
}
