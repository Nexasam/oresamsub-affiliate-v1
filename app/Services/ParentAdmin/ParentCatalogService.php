<?php

namespace App\Services\ParentAdmin;

use App\Models\ParentBusiness;
use App\Models\ProductPlan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ParentCatalogService
{
    public function plans(ParentBusiness $parent, int $perPage = 50): LengthAwarePaginator
    {
        return ProductPlan::query()
            ->where('parent_business_id', $parent->id)
            ->with([
                'product_plan_category:id,product_id,network_id,product_plan_category_name',
                'product_plan_category.product:id,product_name',
                'product_plan_category.network:id,network_name',
            ])
            ->latest()
            ->paginate($perPage);
    }

    public function createPlan(ParentBusiness $parent, array $attributes): ProductPlan
    {
        return $parent->productPlans()->create($attributes);
    }

    public function updatePlan(ParentBusiness $parent, ProductPlan $plan, array $attributes): ProductPlan
    {
        abort_unless($plan->parent_business_id === $parent->id, 404);

        $plan->update($attributes);

        return $plan->fresh(['product_plan_category.product', 'product_plan_category.network']);
    }
}
