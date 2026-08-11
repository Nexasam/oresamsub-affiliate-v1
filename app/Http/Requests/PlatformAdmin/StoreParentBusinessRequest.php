<?php

namespace App\Http\Requests\PlatformAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreParentBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform_admin') !== null;
    }

    public function rules(): array
    {
        return [
            'business' => ['required', 'array'],
            'business.name' => ['required', 'string', 'max:255'],
            'business.slug' => ['required', 'alpha_dash', 'max:100', Rule::unique('parent_businesses', 'slug')],
            'business.contact_email' => ['nullable', 'email:rfc', 'max:255'],
            'business.contact_phone' => ['nullable', 'string', 'max:30'],
            'business.status' => ['required', Rule::in(['active', 'inactive'])],
            'admin' => ['required', 'array'],
            'admin.name' => ['required', 'string', 'max:255'],
            'admin.email' => ['required', 'email:rfc', 'max:255', Rule::unique('parent_admins', 'email')],
            'admin.password' => ['required', 'string', Password::min(12)->letters()->mixedCase()->numbers()],
            'admin.active' => ['required', 'boolean'],
            'admin.must_change_password' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $business = $this->input('business', []);
        $business['slug'] = strtolower(trim((string) ($business['slug'] ?? '')));
        $business['contact_email'] = filled($business['contact_email'] ?? null)
            ? strtolower(trim((string) $business['contact_email']))
            : null;

        $admin = $this->input('admin', []);
        $admin['email'] = strtolower(trim((string) ($admin['email'] ?? '')));

        $this->merge(['business' => $business, 'admin' => $admin]);
    }
}
