<?php

namespace App\Http\Requests\ParentAdmin;

use App\Models\ProductPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Support\Collection;

class BulkUpdateProductPlansRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['selection_scope' => $this->input('selection_scope', 'selected')]);
    }

    public function authorize(): bool
    {
        return $this->user('parent_admin') !== null;
    }

    public function rules(): array
    {
        return [
            'selection_scope' => ['required', Rule::in(['selected', 'all'])],
            'plan_ids' => ['required_if:selection_scope,selected', 'nullable', 'array', 'min:1', 'max:2000'],
            'plan_ids.*' => ['required', 'integer', 'distinct'],
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:product_plan_categories,id'],
            'action' => ['required', Rule::in([
                'activate', 'deactivate', 'show_affiliates', 'hide_affiliates',
                'show_public', 'hide_public',
            ])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $parent = $this->user('parent_admin')->parentBusiness;
            $ids = collect($this->input('plan_ids', []))->map(fn ($id) => (int) $id)->unique()->values();
            $plans = $this->selectedPlans();

            if ($this->input('selection_scope') === 'selected' && $plans->count() !== $ids->count()) {
                $validator->errors()->add('plan_ids', 'One or more selected plans do not belong to this parent.');
                return;
            }

            if ($plans->isEmpty()) {
                $validator->errors()->add('plan_ids', 'No product plans match this selection.');
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

    public function selectedPlans(): Collection
    {
        $parent = $this->user('parent_admin')->parentBusiness;
        $query = ProductPlan::query()->where('parent_business_id', $parent->id);

        if ($this->input('selection_scope') === 'all') {
            $query->when($this->input('search'), function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('product_plan_name', 'like', "%{$search}%")
                        ->orWhereHas('product_plan_category', function ($category) use ($search) {
                            $category->where('product_plan_category_name', 'like', "%{$search}%")
                                ->orWhereHas('network', fn ($network) => $network->where('network_name', 'like', "%{$search}%"));
                        });
                });
            })->when($this->input('category_id'), fn ($query, $categoryId) => $query->where('product_plan_category_id', $categoryId));
        } else {
            $query->whereIn('id', collect($this->input('plan_ids', []))->map(fn ($id) => (int) $id));
        }

        return $query->get();
    }
}
