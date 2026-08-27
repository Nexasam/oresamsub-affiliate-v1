<?php

namespace App\Http\Requests\ParentAdmin;

use Illuminate\Foundation\Http\FormRequest;

class SwitchProductPlanRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('parent_admin') !== null;
    }

    public function rules(): array
    {
        return [
            'parent_provider_connection_id' => ['required', 'integer'],
            'provider_plan_id' => ['required', 'string', 'max:255'],
        ];
    }
}

