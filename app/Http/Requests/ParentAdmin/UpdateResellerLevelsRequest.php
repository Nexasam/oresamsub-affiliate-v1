<?php

namespace App\Http\Requests\ParentAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResellerLevelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('parent_admin') !== null;
    }

    public function rules(): array
    {
        return [
            'levels' => ['required', 'array', 'min:1', 'max:6'],
            'levels.*.id' => ['nullable', 'integer'],
            'levels.*.position' => ['required', 'integer', 'between:1,6', 'distinct'],
            'levels.*.name' => ['required', 'string', 'max:255'],
        ];
    }
}
