<?php

namespace App\Http\Requests\ParentAdmin;

use App\Models\ProductPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('parent_admin') !== null;
    }

    public function rules(): array
    {
        return [
            'product_plan_name' => ['sometimes', 'required', 'string', 'max:255'],
            'product_plan_category_id' => ['sometimes', 'required', 'integer', Rule::exists('product_plan_categories', 'id')],
            'cost_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'profit_category' => ['sometimes', 'required', Rule::in(['flat', 'percent'])],
            'visibility' => ['sometimes', 'boolean'],
            'affiliate_visibility' => ['sometimes', 'boolean'],
            'public_visibility' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var ProductPlan|null $plan */
            $plan = $this->route('plan');
            $parent = $this->user('parent_admin')->parentBusiness;
            if (! $plan || $plan->parent_business_id !== $parent->id || $parent->slug === 'oresamsub') {
                return;
            }

            $willBeActive = $this->has('visibility')
                ? $this->boolean('visibility')
                : (bool) $plan->visibility;
            $affiliateVisible = $this->has('affiliate_visibility')
                ? $this->boolean('affiliate_visibility')
                : (bool) $plan->affiliate_visibility;
            $publicVisible = $this->has('public_visibility')
                ? $this->boolean('public_visibility')
                : (bool) $plan->public_visibility;

            if (! $willBeActive && ($affiliateVisible || $publicVisible)) {
                $validator->errors()->add('visibility', 'A hidden draft cannot be visible to affiliates or the public.');
            }

            if (! $willBeActive) {
                return;
            }

            $hasApprovedRoute = $plan->providerRoutes()
                ->where('priority', 1)
                ->whereNotNull('provider_plan_id')
                ->whereHas('parentProviderConnection', function ($query) use ($parent) {
                    $query->where('parent_business_id', $parent->id)
                        ->where('status', 'active')
                        ->where('approval_status', 'approved')
                        ->whereHas('providerConnection', fn ($provider) => $provider->where('status', 'active'));
                })->exists();

            if (! $hasApprovedRoute) {
                $validator->errors()->add('visibility', 'This plan needs an approved primary provider route before it can be activated.');
            }

            $activeLevelIds = $parent->resellerLevels()->where('status', 'active')->pluck('id')->sort()->values();
            $pricedLevelIds = $plan->parentPrices()->pluck('parent_reseller_level_id')->sort()->values();
            if ($activeLevelIds->isEmpty() || $pricedLevelIds->all() !== $activeLevelIds->all()) {
                $validator->errors()->add('visibility', 'This plan needs one acquisition price for every active reseller level before it can be activated.');
            }
        }];
    }
}
