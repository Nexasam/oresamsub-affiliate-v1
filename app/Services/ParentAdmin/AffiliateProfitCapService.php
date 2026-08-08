<?php

namespace App\Services\ParentAdmin;

use App\Models\Affiliate;
use App\Models\AffiliateProductPlan;
use App\Models\AffiliateServiceProfitCap;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPlanCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AffiliateProfitCapService
{
    public function __construct(private readonly ParentProfitRuleService $profitRules) {}

    public function ensureCaps(Affiliate $affiliate): Collection
    {
        abort_unless($affiliate->parent_business_id, 422, 'Affiliate must belong to a parent business.');

        foreach ($this->profitRules->supportedProducts() as $product) {
            $type = $this->typeFor($product);
            foreach (range(1, 6) as $level) {
                $default = $type === 'flat' ? 70.0 : 1.0;
                $highest = $this->highestExistingProfit($affiliate, $product, $level);
                AffiliateServiceProfitCap::query()->firstOrCreate(
                    ['affiliate_id' => $affiliate->id, 'product_id' => $product->id, 'customer_level' => $level],
                    [
                        'parent_business_id' => $affiliate->parent_business_id,
                        'calculation_type' => $type,
                        'max_value' => max($default, $highest),
                    ],
                );
            }
        }

        return $this->caps($affiliate);
    }

    public function caps(Affiliate $affiliate): Collection
    {
        return $affiliate->serviceProfitCaps()->with('product:id,product_name')->orderBy('product_id')->orderBy('customer_level')->get();
    }

    public function typeFor(Product $product): string
    {
        return in_array($this->profitRules->serviceKey($product), ['airtime', 'electricity'], true) ? 'percent' : 'flat';
    }

    public function replaceCaps(Affiliate $affiliate, array $submitted): array
    {
        return DB::transaction(function () use ($affiliate, $submitted) {
            $this->ensureCaps($affiliate);
            $expected = $this->profitRules->supportedProducts()->flatMap(fn ($product) => collect(range(1, 6))
                ->map(fn ($level) => $product->id.':'.$level))->sort()->values();
            $actual = collect($submitted)->map(fn ($cap) => ((int) $cap['product_id']).':'.((int) $cap['customer_level']))->sort()->values();
            if ($actual->all() !== $expected->all() || $actual->unique()->count() !== $actual->count()) {
                throw ValidationException::withMessages(['caps' => 'Provide exactly one cap for every supported service and customer level.']);
            }

            $products = $this->profitRules->supportedProducts()->keyBy('id');
            foreach ($submitted as $cap) {
                $product = $products->get((int) $cap['product_id']);
                if (! $product || $cap['calculation_type'] !== $this->typeFor($product)) {
                    throw ValidationException::withMessages(['caps' => 'Each cap must use the calculation type assigned to its service.']);
                }
            }

            $violations = $this->violations($affiliate, $submitted);
            if ($violations->isNotEmpty()) {
                return ['caps' => $this->caps($affiliate), 'violations' => $violations];
            }

            foreach ($submitted as $cap) {
                AffiliateServiceProfitCap::query()->updateOrCreate(
                    ['affiliate_id' => $affiliate->id, 'product_id' => $cap['product_id'], 'customer_level' => $cap['customer_level']],
                    [
                        'parent_business_id' => $affiliate->parent_business_id,
                        'calculation_type' => $cap['calculation_type'],
                        'max_value' => $cap['max_value'],
                    ],
                );
            }

            return ['caps' => $this->caps($affiliate), 'violations' => collect()];
        });
    }

    public function violations(Affiliate $affiliate, array $submitted): Collection
    {
        $caps = collect($submitted)->keyBy(fn ($cap) => $cap['product_id'].':'.$cap['customer_level']);
        $plans = AffiliateProductPlan::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $affiliate->id)
            ->with('product_plan.product_plan_category.product:id,product_name')
            ->get();

        return $plans->flatMap(function ($plan) use ($caps) {
            $product = $plan->product_plan?->product_plan_category?->product;
            if (! $product) {
                return [];
            }

            return collect(range(1, 6))->map(function ($level) use ($plan, $product, $caps) {
                $cap = $caps->get($product->id.':'.$level);
                $profit = (float) ($plan->{"user_level_{$level}_profit"} ?? 0);
                if (! $cap || $profit <= (float) $cap['max_value']) {
                    return null;
                }

                return [
                    'affiliate_product_plan_id' => $plan->id,
                    'plan_name' => $plan->product_plan_name,
                    'product_id' => $product->id,
                    'product_name' => $product->product_name,
                    'customer_level' => $level,
                    'existing_profit' => $profit,
                    'proposed_maximum' => (float) $cap['max_value'],
                ];
            })->filter();
        })->values();
    }

    public function assertPlanMarginsWithinCaps(AffiliateProductPlan $plan): void
    {
        $productId = ProductPlanCategory::query()
            ->whereKey(ProductPlan::query()->whereKey($plan->product_plan_id)->value('product_plan_category_id'))
            ->value('product_id');
        if (! $productId) {
            return;
        }

        $caps = AffiliateServiceProfitCap::query()
            ->where('affiliate_id', $plan->affiliate_id)
            ->where('product_id', $productId)
            ->pluck('max_value', 'customer_level');
        foreach (range(1, 6) as $level) {
            $column = "user_level_{$level}_profit";
            if (! $plan->isDirty($column) || ! $caps->has($level)) {
                continue;
            }
            if ((float) $plan->{$column} > (float) $caps->get($level)) {
                throw ValidationException::withMessages([
                    $column => "Customer level {$level} profit cannot exceed {$caps->get($level)}.",
                ]);
            }
        }
    }

    private function highestExistingProfit(Affiliate $affiliate, Product $product, int $level): float
    {
        return AffiliateProductPlan::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $affiliate->id)
            ->whereHas('product_plan.product_plan_category', fn ($category) => $category->where('product_id', $product->id))
            ->get()
            ->max(fn ($plan) => (float) ($plan->{"user_level_{$level}_profit"} ?? 0)) ?? 0.0;
    }
}
