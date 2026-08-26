<?php

namespace App\Services\Providers;

use App\Models\ParentProviderConnection;

class ProviderConnectionConfigurationResolver
{
    public function settings(ParentProviderConnection $connection): array
    {
        $connection->loadMissing('providerConnection');
        $shared = $connection->providerConnection?->settings;

        return is_array($shared) && $shared !== [] ? $shared : ($connection->settings ?? []);
    }

    public function baseUrl(ParentProviderConnection $connection): ?string
    {
        $connection->loadMissing('providerConnection');

        return $connection->providerConnection?->base_url ?: $connection->base_url;
    }
}
