<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProviderProductRegistry
{
    public const FALLBACK_PRODUCTS = [
        'data' => 'Data',
        'airtime' => 'Airtime',
        'utility_bills' => 'Utility bills',
        'cable_subscription' => 'Cable subscription',
        'e_pins' => 'E-Pins',
        'result_checker' => 'Result checker',
    ];

    public const LEGACY_ALIASES = [
        'electricity' => 'utility_bills',
        'cable' => 'cable_subscription',
    ];

    public function products(): Collection
    {
        $products = Product::query()
            ->whereNotNull('slug')
            ->when(
                Product::query()->whereNotNull('active_status')->exists(),
                fn ($query) => $query->where('active_status', 1)
            )
            ->orderBy('id')
            ->get(['id', 'product_name', 'slug'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->product_name,
                'slug' => $this->normalize($product->slug),
            ])
            ->unique('slug')
            ->values();

        if ($products->isNotEmpty()) {
            return $products;
        }

        return collect(self::FALLBACK_PRODUCTS)->map(
            fn (string $name, string $slug) => ['id' => null, 'name' => $name, 'slug' => $slug]
        )->values();
    }

    public function slugs(bool $includeLegacy = false): array
    {
        $slugs = $this->products()->pluck('slug')->all();

        return $includeLegacy ? array_values(array_unique([...$slugs, ...array_keys(self::LEGACY_ALIASES)])) : $slugs;
    }

    public function normalize(string $slug): string
    {
        $slug = strtolower(trim($slug));

        return self::LEGACY_ALIASES[$slug] ?? $slug;
    }

    public function normalizeCapabilities(?array $capabilities): array
    {
        $capabilities ??= [];
        $capabilities['services'] = array_values(array_unique(array_map(
            fn ($service) => $this->normalize((string) $service),
            $capabilities['services'] ?? []
        )));

        return $capabilities;
    }
}
