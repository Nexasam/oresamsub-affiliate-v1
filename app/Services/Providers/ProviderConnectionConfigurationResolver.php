<?php

namespace App\Services\Providers;

use App\Models\ParentProviderConnection;
use Illuminate\Support\Arr;

class ProviderConnectionConfigurationResolver
{
    public function settings(ParentProviderConnection $connection): array
    {
        $connection->loadMissing(['providerAdapter', 'providerConnection.providerAdapter']);
        $adapter = $connection->providerConnection?->providerAdapter?->settings
            ?? $connection->providerAdapter?->settings
            ?? [];
        $shared = $connection->providerConnection?->settings ?? [];
        $parent = Arr::except($connection->settings ?? [], ['is_primary']);

        return array_replace_recursive(
            is_array($adapter) ? $adapter : [],
            is_array($shared) ? $shared : [],
            $parent,
        );
    }

    public function baseUrl(ParentProviderConnection $connection): ?string
    {
        $connection->loadMissing('providerConnection');

        return $connection->providerConnection?->base_url ?: $connection->base_url;
    }
}
