<?php

namespace App\Http\Requests\ParentAdmin\Concerns;

use App\Models\ParentProviderConnection;
use Brick\Math\BigDecimal;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesParentProductPlan
{
    protected function productPlanRules(string $prefix = ''): array
    {
        return [
            "{$prefix}product_plan_name" => ['required', 'string', 'max:255'],
            "{$prefix}product_plan_category_id" => ['required', 'integer', Rule::exists('product_plan_categories', 'id')],
            "{$prefix}api_id" => ['nullable', 'string', 'max:255'],
            "{$prefix}admin_cost_price" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}cost_price" => ['required', 'numeric', 'min:0'],
            "{$prefix}data_size_in_mb" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}validity_in_days" => ['nullable', 'integer', 'min:0'],
            "{$prefix}profit_category" => ['required', Rule::in(['flat', 'percent'])],
            "{$prefix}commission_feature" => ['sometimes', 'boolean'],
            "{$prefix}upline_commission_option" => ['nullable', Rule::in(['flat', 'percent'])],
            "{$prefix}upline_percentage_commission" => ['nullable', 'numeric', 'between:0,100'],
            "{$prefix}upline_flat_commission" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}upline_commission_cap" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}visibility" => ['required', 'boolean'],
            "{$prefix}affiliate_visibility" => ['required', 'boolean'],
            "{$prefix}public_visibility" => ['required', 'boolean'],
            "{$prefix}route" => ['nullable', 'array'],
            "{$prefix}route.parent_provider_connection_id" => ['nullable', 'integer'],
            "{$prefix}route.provider_plan_id" => ['nullable', 'string', 'max:255'],
            "{$prefix}prices" => ['nullable', 'array', 'max:6'],
            "{$prefix}prices.*.parent_reseller_level_id" => ['required', 'integer'],
            "{$prefix}prices.*.selling_price" => ['required', 'numeric', 'min:0'],
            "{$prefix}prices.*.max_profit" => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function validateProductPlanPayload(Validator $validator, array $plan, string $path = ''): void
    {
        $parent = $this->user('parent_admin')->parentBusiness;
        $key = fn (string $field) => $path === '' ? $field : "{$path}.{$field}";
        $isActive = filter_var($plan['visibility'] ?? false, FILTER_VALIDATE_BOOL);
        $isExternalActivePlan = $parent->slug !== 'oresamsub' && $isActive;
        $route = $plan['route'] ?? [];

        if (! $isActive && (filter_var($plan['affiliate_visibility'] ?? false, FILTER_VALIDATE_BOOL)
            || filter_var($plan['public_visibility'] ?? false, FILTER_VALIDATE_BOOL))) {
            $validator->errors()->add($key('visibility'), 'A hidden draft cannot be visible to affiliates or the public.');
        }

        if ($isExternalActivePlan && blank($route['parent_provider_connection_id'] ?? null)) {
            $validator->errors()->add($key('route.parent_provider_connection_id'), 'Select an approved provider connection before activating this plan.');
        }
        if ($isExternalActivePlan && blank($route['provider_plan_id'] ?? null)) {
            $validator->errors()->add($key('route.provider_plan_id'), 'Enter the provider external plan ID before activating this plan.');
        }

        if (filled($route['parent_provider_connection_id'] ?? null)) {
            $validConnection = ParentProviderConnection::query()
                ->whereKey($route['parent_provider_connection_id'])
                ->where('parent_business_id', $parent->id)
                ->where('status', 'active')
                ->where('approval_status', 'approved')
                ->whereHas('providerConnection', fn ($query) => $query->where('status', 'active'))
                ->exists();
            if (! $validConnection) {
                $validator->errors()->add($key('route.parent_provider_connection_id'), 'The provider connection must be active, approved and owned by this parent.');
            }
        }

        $activeLevelIds = $parent->resellerLevels()->where('status', 'active')->orderBy('position')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $priceLevelIds = collect($plan['prices'] ?? [])->pluck('parent_reseller_level_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $expectedLevelIds = collect($activeLevelIds)->sort()->values()->all();

        if (count($priceLevelIds) !== count(array_unique($priceLevelIds))) {
            $validator->errors()->add($key('prices'), 'Each reseller level may appear only once per plan.');
        }

        if ($isExternalActivePlan && $priceLevelIds !== $expectedLevelIds) {
            $validator->errors()->add($key('prices'), 'Provide exactly one acquisition price for every active reseller level.');
        } elseif (array_diff($priceLevelIds, $expectedLevelIds) !== []) {
            $validator->errors()->add($key('prices'), 'Every reseller price must belong to this parent.');
        }

        if (is_numeric($plan['cost_price'] ?? null)) {
            $cost = BigDecimal::of((string) $plan['cost_price']);
            foreach ($plan['prices'] ?? [] as $index => $price) {
                if (is_numeric($price['selling_price'] ?? null)
                    && BigDecimal::of((string) $price['selling_price'])->isLessThan($cost)) {
                    $validator->errors()->add($key("prices.{$index}.selling_price"), 'A reseller price cannot be below the provider cost.');
                }
            }
        }
    }
}
