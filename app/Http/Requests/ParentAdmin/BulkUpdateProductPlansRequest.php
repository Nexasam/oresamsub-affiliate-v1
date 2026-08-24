<?php

namespace App\Http\Requests\ParentAdmin;

use App\Models\ProductPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkUpdateProductPlansRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('parent_admin') !== null;
    }

    public function rules(): array
    {
        return [
            'plan_ids' => ['required', 'array', 'min:1', 'max:100'],
            'plan_ids.*' => ['required', 'integer', 'distinct'],
            'action' => ['required', Rule::in([
                'activate', 'deactivate', 'show_affiliates', 'hide_affiliates',
                'show_public', 'hide_public',
            ])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $parent = $this->user('parent_admin')->parentBusiness;
            $ids = collect($this->input('plan_ids', []))->map(fn ($id) => (int) $id)->unique()->values();
            $plans = ProductPlan::query()->where('parent_business_id', $parent->id)->whereIn('id', $ids)->get();

            if ($plans->count() !== $ids->count()) {
                $validator->errors()->add('plan_ids', 'One or more selected plans do not belong to this parent.');
                return;
            }

            if (in_array($this->input('action'), ['show_affiliates', 'show_public'], true) && $plans->contains(fn ($plan) => ! $plan->visibility)) {
                $validator->errors()->add('plan_ids', 'Activate every selected plan before making it available.');
            }

            if ($this->input('action') !== 'activate' || $parent->slug === 'oresamsub') {
                return;
            }

            $levelIds = $parent->resellerLevels()->where('status', 'active')->pluck('id')->sort()->values()->all();
            foreach ($plans as $plan) {
                $hasRoute = $plan->providerRoutes()->where('priority', 1)->whereNotNull('provider_plan_id')
                    ->whereHas('parentProviderConnection', fn ($query) => $query
                        ->where('parent_business_id', $parent->id)->where('status', 'active')->where('approval_status', 'approved')
                        ->whereHas('providerConnection', fn ($provider) => $provider->where('status', 'active')))->exists();
                $pricedIds = $plan->parentPrices()->pluck('parent_reseller_level_id')->sort()->values()->all();
                if (! $hasRoute || $levelIds === [] || $pricedIds !== $levelIds) {
                    $validator->errors()->add('plan_ids', "{$plan->product_plan_name} needs an approved route and complete reseller prices before activation.");
                }
            }
        }];
    }
}
