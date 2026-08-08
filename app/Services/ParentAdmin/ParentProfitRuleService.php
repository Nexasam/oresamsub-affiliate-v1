<?php

namespace App\Services\ParentAdmin;

use App\Models\ParentBusiness;
use App\Models\ParentDefaultProfitRule;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParentProfitRuleService
{
    public function ensureDefaults(ParentBusiness $parent): Collection
    {
        $levels = $parent->resellerLevels()->where('status', 'active')->get();
        $products = $this->supportedProducts();

        foreach ($levels as $level) {
            foreach ($products as $product) {
                $type = $this->calculationType($product);
                ParentDefaultProfitRule::query()->firstOrCreate(
                    [
                        'parent_business_id' => $parent->id,
                        'parent_reseller_level_id' => $level->id,
                        'product_id' => $product->id,
                    ],
                    ['calculation_type' => $type, 'value' => $type === 'flat' ? 50 : 1],
                );
            }
        }

        return $this->rules($parent);
    }

    public function replaceDefaults(ParentBusiness $parent, array $rules): Collection
    {
        return DB::transaction(function () use ($parent, $rules) {
            $levelIds = $parent->resellerLevels()->where('status', 'active')->pluck('id');
            $productIds = $this->supportedProducts()->pluck('id');

            foreach ($rules as $rule) {
                if (! $levelIds->contains((int) $rule['parent_reseller_level_id']) || ! $productIds->contains((int) $rule['product_id'])) {
                    throw ValidationException::withMessages(['rules' => 'Every rule must use this parent’s active level and a supported service.']);
                }
                if ($rule['calculation_type'] === 'percent_discount' && (float) $rule['value'] > 100) {
                    throw ValidationException::withMessages(['rules' => 'Percentage discounts cannot exceed 100%.']);
                }

                ParentDefaultProfitRule::query()->updateOrCreate(
                    [
                        'parent_business_id' => $parent->id,
                        'parent_reseller_level_id' => $rule['parent_reseller_level_id'],
                        'product_id' => $rule['product_id'],
                    ],
                    ['calculation_type' => $rule['calculation_type'], 'value' => $rule['value']],
                );
            }

            return $this->rules($parent);
        });
    }

    public function supportedProducts(): Collection
    {
        return Product::query()->get()->filter(fn (Product $product) => $this->serviceKey($product) !== null)->values();
    }

    public function rules(ParentBusiness $parent): Collection
    {
        return $parent->defaultProfitRules()->with(['product:id,product_name', 'parentResellerLevel:id,name,position'])->get();
    }

    public function serviceKey(Product $product): ?string
    {
        $name = strtolower($product->product_name.' '.$product->slug);
        foreach (['electricity', 'airtime', 'cable', 'data'] as $service) {
            if (str_contains($name, $service)) {
                return $service;
            }
        }

        return null;
    }

    public function calculationType(Product $product): string
    {
        return in_array($this->serviceKey($product), ['airtime', 'electricity'], true) ? 'percent_discount' : 'flat';
    }
}
