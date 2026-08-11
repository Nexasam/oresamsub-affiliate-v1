<?php

namespace App\Services\Providers;

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Support\ProviderProductRegistry;
use Illuminate\Validation\ValidationException;

class PurchaseRouteResolver
{
    public function __construct(private readonly ProviderProductRegistry $products) {}

    /**
     * Resolve the approved provider route for a purchase without executing it.
     *
     * @return array<string, mixed>
     */
    public function resolve(AffiliateProductPlan $affiliatePlan): array
    {
        $affiliatePlan->loadMissing([
            'product_plan.product_plan_category.product',
            'product_plan.providerRoutes.parentProviderConnection.providerConnection',
        ]);

        $affiliate = Affiliate::query()
            ->with('parentBusiness')
            ->find($affiliatePlan->affiliate_id);
        $plan = $affiliatePlan->product_plan;

        if (! $affiliate || ! $affiliate->parent_business_id || ! $affiliate->parentBusiness) {
            $this->fail('The affiliate is not assigned to an active parent purchase context.');
        }

        if (! $plan || (int) $plan->parent_business_id !== (int) $affiliate->parent_business_id) {
            $this->fail('The selected plan does not belong to the affiliate parent.');
        }

        if (! (bool) $affiliatePlan->visibility || ! (bool) $affiliatePlan->visibility_from_admin) {
            $this->fail('The affiliate plan is not available for purchase.');
        }

        if (! (bool) $plan->visibility || ! (bool) $plan->affiliate_visibility) {
            $this->fail('The parent product plan is not available for affiliates.');
        }

        $route = $plan->providerRoutes
            ->where('priority', 1)
            ->where('active', true)
            ->sortBy('id')
            ->first();

        if (! $route || (int) $route->parent_business_id !== (int) $affiliate->parent_business_id) {
            $this->fail('No active primary provider route is configured for this plan.');
        }

        $connection = $route->parentProviderConnection;

        if (! $connection
            || (int) $connection->parent_business_id !== (int) $affiliate->parent_business_id
            || $connection->status !== 'active'
            || $connection->approval_status !== 'approved') {
            $this->fail('The primary provider connection is not active and approved.');
        }

        $adapter = $connection->providerConnection;

        if (! $adapter || $adapter->status !== 'active') {
            $this->fail('The configured provider adapter is inactive.');
        }

        $productSlug = $plan->product_plan_category?->product?->slug;

        if (! is_string($productSlug) || trim($productSlug) === '') {
            $this->fail('The plan does not resolve to a supported product service.');
        }

        $productSlug = $this->products->normalize($productSlug);
        $capabilities = $this->products->normalizeCapabilities($adapter->capabilities);

        if (! in_array($productSlug, $capabilities['services'] ?? [], true)) {
            $this->fail('The provider adapter does not support this product service.');
        }

        if (! is_string($route->provider_plan_id) || trim($route->provider_plan_id) === '') {
            $this->fail('The provider plan reference is missing.');
        }

        return [
            'affiliate' => $affiliate,
            'parent' => $affiliate->parentBusiness,
            'affiliate_product_plan' => $affiliatePlan,
            'product_plan' => $plan,
            'route' => $route,
            'connection' => $connection,
            'adapter' => $adapter,
            'provider_plan_id' => $route->provider_plan_id,
            'product_slug' => $productSlug,
        ];
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['purchase_route' => $message]);
    }
}
