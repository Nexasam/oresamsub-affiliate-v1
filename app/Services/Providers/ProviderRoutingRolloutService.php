<?php

namespace App\Services\Providers;

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\ProviderRoutingRollout;

class ProviderRoutingRolloutService
{
    public function enabledFor(AffiliateProductPlan $affiliatePlan): bool
    {
        if (! config('parent_businesses.features.provider_routing')) {
            return false;
        }

        $affiliatePlan->loadMissing('product_plan.product_plan_category.product');
        $affiliate = Affiliate::query()->find($affiliatePlan->affiliate_id);
        $parentId = $affiliate?->parent_business_id;
        $service = $affiliatePlan->product_plan?->product_plan_category?->product?->slug;

        if (! $affiliate || ! $parentId || ! $service) {
            return false;
        }

        $affiliateRule = ProviderRoutingRollout::query()
            ->where('parent_business_id', $parentId)
            ->where('scope_type', 'affiliate')
            ->where('scope_id', $affiliate->id)
            ->where('service', $service)
            ->first();

        if ($affiliateRule) {
            return $affiliateRule->enabled;
        }

        return (bool) ProviderRoutingRollout::query()
            ->where('parent_business_id', $parentId)
            ->where('scope_type', 'parent')
            ->where('scope_id', $parentId)
            ->where('service', $service)
            ->value('enabled');
    }
}
