<?php

namespace App\Models;

use App\Services\ParentAdmin\AffiliateProfitCapService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class AffiliateProductPlan extends AffiliateScopedModel
{
    use HasFactory;

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (AffiliateProductPlan $affiliatePlan) {
            $affiliateParentId = Affiliate::query()->whereKey($affiliatePlan->affiliate_id)->value('parent_business_id');
            $planParentId = ProductPlan::query()->whereKey($affiliatePlan->product_plan_id)->value('parent_business_id');

            if ((int) $affiliateParentId !== (int) $planParentId) {
                throw new InvalidArgumentException('Affiliate and product plan must belong to the same parent business.');
            }

            app(AffiliateProfitCapService::class)->assertPlanMarginsWithinCaps($affiliatePlan);
        });
    }

    // TODO: revamp productplan with global scope for visibility in all its instance in the code

    protected $guarded = ['id'];

    public function scopeCustomerAvailable(Builder $query): Builder
    {
        return $query
            ->where($query->qualifyColumn('visibility'), 1)
            ->where($query->qualifyColumn('visibility_from_admin'), 1)
            ->whereHas('product_plan', fn (Builder $plan) => $plan
                ->where('visibility', 1)
                ->where('affiliate_visibility', 1)
                ->whereHas('providerRoutes', fn (Builder $route) => $route
                    ->where('priority', 1)
                    ->where('active', true)
                    ->whereHas('parentProviderConnection', fn (Builder $connection) => $connection
                        ->where('status', 'active')
                        ->where('approval_status', 'approved')
                        ->whereHas('providerConnection', fn (Builder $adapter) => $adapter->where('status', 'active'))
                    )
                )
            );
    }

    /** @return array{available: bool, reason: string|null} */
    public function availabilityState(): array
    {
        $this->loadMissing('product_plan.providerRoutes.parentProviderConnection.providerConnection');
        $parentState = $this->parentAvailabilityState();
        if (! $parentState['available']) return $parentState;
        if (! (bool) $this->visibility) return ['available' => false, 'reason' => 'affiliate_disabled'];
        if (! (bool) $this->visibility_from_admin) return ['available' => false, 'reason' => 'platform_disabled'];

        return ['available' => true, 'reason' => null];
    }

    /** @return array{available: bool, reason: string|null} */
    public function parentAvailabilityState(): array
    {
        $this->loadMissing('product_plan.providerRoutes.parentProviderConnection.providerConnection');
        $plan = $this->product_plan;

        if (! $plan || ! (bool) $plan->visibility) return ['available' => false, 'reason' => 'parent_disabled'];
        if (! (bool) $plan->affiliate_visibility) return ['available' => false, 'reason' => 'parent_hidden_from_affiliates'];

        $route = $plan->providerRoutes->first(fn ($route) => (int) $route->priority === 1 && (bool) $route->active);
        if (! $route) return ['available' => false, 'reason' => 'route_inactive'];

        $connection = $route->parentProviderConnection;
        if (! $connection || $connection->status !== 'active' || $connection->approval_status !== 'approved') {
            return ['available' => false, 'reason' => 'connection_inactive'];
        }
        if (! $connection->providerConnection || $connection->providerConnection->status !== 'active') {
            return ['available' => false, 'reason' => 'adapter_inactive'];
        }

        return ['available' => true, 'reason' => null];
    }

    /**
     * each card belongs to a product plan
     **/
    public function product_plan()
    {
        return $this->belongsTo(ProductPlan::class, 'product_plan_id', 'id');
    }

    /**
     * each product plan belongs to a product
     **/
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
        // return $this->belongsTo(Product::class, 'product_id', 'id')->where('active_status',1);
    }

    /**
     * each product plan belongs to a product_plan_category === nullable
     **/
    public function product_plan_category()
    {
        return $this->belongsTo(AffiliateProductPlanCategory::class, 'plan_category_id', 'id');
        // return $this->belongsTo(ProductPlanCategory::class, 'product_plan_category_id', 'id')->where('active_status',1);
    }

    // likely redundant
    public function automation()
    {
        return $this->belongsTo(Automation::class, 'automation_id', 'id');
        // return $this->belongsTo(ProductPlanCategory::class, 'product_plan_category_id', 'id')->where('active_status',1);
    }

    // likely redundant
    public function reprocess_automation()
    {
        return $this->belongsTo(Automation::class, 'reprocess_automation_id', 'id');
        // return $this->belongsTo(ProductPlanCategory::class, 'product_plan_category_id', 'id')->where('active_status',1);
    }
}
