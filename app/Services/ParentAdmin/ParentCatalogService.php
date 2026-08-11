<?php

namespace App\Services\ParentAdmin;

use App\Models\Affiliate;
use App\Models\ParentBusiness;
use App\Models\ParentResellerLevel;
use App\Models\ProductPlan;
use App\Models\ProductPlanParentPrice;
use Brick\Math\BigDecimal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParentCatalogService
{
    public function plans(ParentBusiness $parent, int $perPage = 50, array $filters = []): LengthAwarePaginator
    {
        $query = ProductPlan::query()
            ->where('parent_business_id', $parent->id)
            ->with([
                'product_plan_category:id,product_id,network_id,product_plan_category_name',
                'product_plan_category.product:id,product_name',
                'product_plan_category.network:id,network_name',
                'providerRoutes' => fn ($query) => $query->where('priority', 1)->orderBy('id'),
                'providerRoutes.parentProviderConnection:id,name',
                'parentPrices.parentResellerLevel:id,name,position',
            ]);

        $query->when($filters['search'] ?? null, function ($query, string $search) {
            $query->where(function ($query) use ($search) {
                $query->where('product_plan_name', 'like', "%{$search}%")
                    ->orWhereHas('product_plan_category', function ($category) use ($search) {
                        $category->where('product_plan_category_name', 'like', "%{$search}%")
                            ->orWhereHas('network', fn ($network) => $network->where('network_name', 'like', "%{$search}%"));
                    });
            });
        });
        $query->when($filters['product_id'] ?? null, fn ($query, $productId) => $query->whereHas('product_plan_category', fn ($category) => $category->where('product_id', $productId))
        );
        $query->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('product_plan_category_id', $categoryId)
        );
        $query->when(($filters['pricing_status'] ?? null) === 'custom', fn ($query) => $query->whereHas('parentPrices'));
        $query->when(($filters['pricing_status'] ?? null) === 'inherited', fn ($query) => $query->whereDoesntHave('parentPrices'));

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function createPlan(ParentBusiness $parent, array $attributes): ProductPlan
    {
        return DB::transaction(function () use ($parent, $attributes) {
            $route = $attributes['route'] ?? null;
            $prices = $attributes['prices'] ?? [];
            unset($attributes['route'], $attributes['prices']);

            $plan = $parent->productPlans()->create($attributes);

            if ($route && filled($route['parent_provider_connection_id'] ?? null) && filled($route['provider_plan_id'] ?? null)) {
                $plan->providerRoutes()->create([
                    'parent_business_id' => $parent->id,
                    'parent_provider_connection_id' => $route['parent_provider_connection_id'],
                    'provider_plan_id' => $route['provider_plan_id'],
                    'priority' => 1,
                    'active' => (bool) ($attributes['visibility'] ?? false),
                ]);
            }

            if ($prices !== []) {
                $this->updatePrices($parent, $plan, $prices);
            }

            return $this->hydratePlan($plan);
        });
    }

    public function createPlans(ParentBusiness $parent, array $plans): Collection
    {
        return DB::transaction(fn () => collect($plans)->map(
            fn (array $attributes) => $this->createPlan($parent, $attributes)
        ));
    }

    private function hydratePlan(ProductPlan $plan): ProductPlan
    {
        return $plan->fresh([
            'product_plan_category.product',
            'product_plan_category.network',
            'providerRoutes.parentProviderConnection:id,name',
            'parentPrices.parentResellerLevel:id,name,position',
        ]);
    }

    public function updatePlan(ParentBusiness $parent, ProductPlan $plan, array $attributes): ProductPlan
    {
        abort_unless($plan->parent_business_id === $parent->id, 404);

        $plan->update($attributes);

        if (array_key_exists('visibility', $attributes)) {
            $plan->providerRoutes()->where('priority', 1)->update([
                'active' => (bool) $attributes['visibility'],
            ]);
        }

        return $this->hydratePlan($plan);
    }

    public function updateConfiguration(ParentBusiness $parent, ProductPlan $plan, array $attributes): ProductPlan
    {
        abort_unless($plan->parent_business_id === $parent->id, 404);

        return DB::transaction(function () use ($parent, $plan, $attributes) {
            $route = $attributes['route'] ?? null;
            $prices = $attributes['prices'] ?? [];
            unset($attributes['route'], $attributes['prices']);
            $plan->update($attributes);

            if ($route && filled($route['parent_provider_connection_id'] ?? null) && filled($route['provider_plan_id'] ?? null)) {
                $plan->providerRoutes()->updateOrCreate(['priority' => 1], [
                    'parent_business_id' => $parent->id,
                    'parent_provider_connection_id' => $route['parent_provider_connection_id'],
                    'provider_plan_id' => $route['provider_plan_id'],
                    'active' => (bool) $attributes['visibility'],
                ]);
            } elseif (! $attributes['visibility']) {
                $plan->providerRoutes()->where('priority', 1)->delete();
            }

            if ($prices !== []) {
                $this->updatePrices($parent, $plan, $prices);
            }

            return $this->hydratePlan($plan);
        });
    }

    public function replaceLevels(ParentBusiness $parent, array $levels): Collection
    {
        return DB::transaction(function () use ($parent, $levels) {
            $expectedPositions = range(1, count($levels));
            $positions = collect($levels)->pluck('position')->sort()->values()->all();
            if ($positions !== $expectedPositions) {
                throw ValidationException::withMessages(['levels' => 'Level positions must be contiguous, starting at one.']);
            }

            $existing = $parent->resellerLevels()->lockForUpdate()->get();
            $submittedIds = collect($levels)->pluck('id')->filter()->map(fn ($id) => (int) $id);
            if ($submittedIds->diff($existing->pluck('id'))->isNotEmpty()) {
                throw ValidationException::withMessages(['levels' => 'Every existing level must belong to this parent business.']);
            }

            foreach ($levels as $attributes) {
                if (! isset($attributes['id'])) {
                    continue;
                }

                $level = $existing->firstWhere('id', (int) $attributes['id']);
                if ($level && $level->position !== (int) $attributes['position']) {
                    throw ValidationException::withMessages(['levels' => 'Existing level positions cannot be reordered; remove only trailing levels.']);
                }
            }

            $omitted = $existing->whereNotIn('id', $submittedIds);
            foreach ($omitted as $level) {
                $isReferenced = Affiliate::query()->where('parent_reseller_level_id', $level->id)->exists()
                    || ProductPlanParentPrice::query()->where('parent_reseller_level_id', $level->id)->exists();
                if ($isReferenced) {
                    throw ValidationException::withMessages(['levels' => "{$level->name} is already in use and must be retained."]);
                }
                $level->defaultProfitRules()->delete();
            }

            foreach ($levels as $attributes) {
                $level = isset($attributes['id'])
                    ? $existing->firstWhere('id', (int) $attributes['id'])
                    : $existing->firstWhere('position', (int) $attributes['position']);

                if ($level) {
                    $level->update(['position' => $attributes['position'], 'name' => $attributes['name'], 'status' => 'active']);
                } else {
                    $parent->resellerLevels()->create(['position' => $attributes['position'], 'name' => $attributes['name'], 'status' => 'active']);
                }
            }

            $omitted->each->update(['status' => 'inactive']);

            return $parent->resellerLevels()->where('status', 'active')->orderBy('position')->get();
        });
    }

    public function generateSixLevels(ParentBusiness $parent): array
    {
        return DB::transaction(function () use ($parent) {
            $names = ['Basic', 'Bronze', 'Silver', 'Gold', 'Diamond', 'Platinum'];
            $created = 0;
            foreach ($names as $index => $name) {
                $position = $index + 1;
                $level = $parent->resellerLevels()->where('position', $position)->first();
                if ($level) {
                    $level->update(['status' => 'active']);
                } else {
                    $parent->resellerLevels()->create(['position' => $position, 'name' => $name, 'status' => 'active']);
                    $created++;
                }
            }

            return [
                'created' => $created,
                'levels' => $parent->resellerLevels()->where('status', 'active')->orderBy('position')->get(),
            ];
        });
    }

    public function updatePrices(ParentBusiness $parent, ProductPlan $plan, array $prices): Collection
    {
        abort_unless($plan->parent_business_id === $parent->id, 404);

        return DB::transaction(function () use ($parent, $plan, $prices) {
            $activeLevels = $parent->resellerLevels()->where('status', 'active')->lockForUpdate()->orderBy('position')->get();
            $submittedIds = collect($prices)->pluck('parent_reseller_level_id')->map(fn ($id) => (int) $id)->sort()->values();
            if ($submittedIds->all() !== $activeLevels->pluck('id')->sort()->values()->all()) {
                throw ValidationException::withMessages(['prices' => 'Provide exactly one price for every active reseller level.']);
            }

            if (is_numeric($plan->cost_price)) {
                $providerCost = BigDecimal::of((string) $plan->cost_price);
                foreach ($prices as $price) {
                    if ($price['inherit'] ?? false) {
                        continue;
                    }
                    if (BigDecimal::of((string) $price['selling_price'])->isLessThan($providerCost)) {
                        throw ValidationException::withMessages(['prices' => 'A reseller price cannot be below the provider cost.']);
                    }
                }
            }

            foreach ($prices as $price) {
                if ($price['inherit'] ?? false) {
                    ProductPlanParentPrice::query()
                        ->where('product_plan_id', $plan->id)
                        ->where('parent_reseller_level_id', $price['parent_reseller_level_id'])
                        ->delete();

                    continue;
                }
                ProductPlanParentPrice::query()->updateOrCreate(
                    [
                        'product_plan_id' => $plan->id,
                        'parent_reseller_level_id' => $price['parent_reseller_level_id'],
                    ],
                    [
                        'parent_business_id' => $parent->id,
                        'selling_price' => $price['selling_price'],
                        'max_profit' => $price['max_profit'] ?? null,
                    ],
                );
            }

            return ProductPlanParentPrice::query()
                ->where('parent_business_id', $parent->id)
                ->where('product_plan_id', $plan->id)
                ->with('parentResellerLevel:id,name,position')
                ->get();
        });
    }

    public function clearPriceOverride(ParentBusiness $parent, ProductPlan $plan, ParentResellerLevel $level): void
    {
        abort_unless($plan->parent_business_id === $parent->id && $level->parent_business_id === $parent->id, 404);

        ProductPlanParentPrice::query()
            ->where('parent_business_id', $parent->id)
            ->where('product_plan_id', $plan->id)
            ->where('parent_reseller_level_id', $level->id)
            ->delete();
    }
}
