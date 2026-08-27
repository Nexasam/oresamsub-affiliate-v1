<?php

namespace App\Services\ParentAdmin;

use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\ProductPlan;
use App\Models\ProductPlanProviderRoute;
use App\Support\ProviderProductRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductPlanRouteSwitchService
{
    public function __construct(private readonly ProviderProductRegistry $products) {}

    public function switch(
        ParentBusiness $parent,
        ProductPlan $plan,
        ParentProviderConnection $connection,
        string $providerPlanId,
    ): ProductPlanProviderRoute {
        abort_unless((int) $plan->parent_business_id === (int) $parent->id, 404);
        abort_unless((int) $connection->parent_business_id === (int) $parent->id, 404);

        $connection->loadMissing(['providerConnection.providerAdapter', 'providerAdapter']);
        if ($connection->status !== 'active' || $connection->approval_status !== 'approved' || $connection->providerConnection?->status !== 'active') {
            throw ValidationException::withMessages(['parent_provider_connection_id' => 'Select an approved, active provider connection.']);
        }

        $plan->loadMissing('product_plan_category.product');
        $service = $this->products->normalize((string) $plan->product_plan_category?->product?->slug);
        $capabilities = $this->products->normalizeCapabilities(
            $connection->providerConnection?->capabilities
                ?: $connection->providerConnection?->providerAdapter?->capabilities
                ?: $connection->providerAdapter?->capabilities
        );
        if (! in_array($service, $capabilities['services'] ?? [], true)) {
            throw ValidationException::withMessages(['parent_provider_connection_id' => 'The selected connection does not support this plan service.']);
        }

        return DB::transaction(function () use ($parent, $plan, $connection, $providerPlanId) {
            $routes = ProductPlanProviderRoute::query()
                ->where('parent_business_id', $parent->id)
                ->where('product_plan_id', $plan->id)
                ->lockForUpdate()
                ->orderBy('priority')
                ->get();
            $current = $routes->firstWhere('priority', 1);
            $target = $routes->firstWhere('parent_provider_connection_id', $connection->id);
            $providerPlanId = trim($providerPlanId);

            if ($target && $current && $target->is($current)) {
                $target->update(['provider_plan_id' => $providerPlanId, 'active' => true]);

                return $target->fresh();
            }

            $temporaryPriority = max(1, (int) $routes->max('priority')) + 1;
            if ($current) {
                $current->update(['priority' => $temporaryPriority, 'active' => false]);
            }

            if ($target) {
                $target->update(['priority' => 1, 'provider_plan_id' => $providerPlanId, 'active' => true]);
            } else {
                $target = ProductPlanProviderRoute::create([
                    'parent_business_id' => $parent->id,
                    'product_plan_id' => $plan->id,
                    'parent_provider_connection_id' => $connection->id,
                    'provider_plan_id' => $providerPlanId,
                    'priority' => 1,
                    'active' => true,
                ]);
            }

            return $target->fresh();
        });
    }
}

