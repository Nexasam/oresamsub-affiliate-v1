<?php

namespace App\Http\Requests\ParentAdmin;

use Illuminate\Foundation\Http\FormRequest;

class CreditAffiliateSettlementWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('parent_admin') !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/', 'decimal:0,2', 'gt:0', 'max:100000000'],
            'reference' => ['required', 'string', 'max:191'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
