<?php

namespace App\Services\PlatformAdmin;

use App\Models\ProviderAdapter;
use App\Models\ProviderConnection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProviderConnectionCatalogueService
{
    public function save(array $data, ?ProviderConnection $connection = null): ProviderConnection
    {
        $adapter = ProviderAdapter::query()->where('status', 'active')->findOrFail($data['provider_adapter_id']);
        $host = $this->host($data['website_url'] ?? $data['base_url'] ?? null);
        if ($host && ProviderConnection::query()
            ->where('provider_adapter_id', $adapter->id)
            ->when($connection, fn ($query) => $query->whereKeyNot($connection->id))
            ->get(['website_url', 'base_url'])->contains(fn ($candidate) => $this->host($candidate->website_url ?: $candidate->base_url) === $host)) {
            throw ValidationException::withMessages(['website_url' => 'A connection for this provider website already exists under the selected adapter.']);
        }

        $snapshot = array_replace_recursive($adapter->settings ?? [], $data['settings_overrides'] ?? []);
        $attributes = [
            'provider_adapter_id' => $adapter->id,
            'name' => trim($data['name']),
            'slug' => Str::slug($data['slug']),
            'adapter' => $adapter->adapter_key,
            'capabilities' => $adapter->capabilities,
            'base_url' => $data['base_url'] ?? null,
            'website_url' => $data['website_url'] ?? null,
            'documentation_url' => $data['documentation_url'] ?? null,
            'settings' => $snapshot ?: null,
            'adapter_version' => $adapter->version,
            'status' => $data['status'],
        ];

        if ($connection) {
            $connection->update($attributes);
            return $connection->fresh('providerAdapter');
        }

        return ProviderConnection::create($attributes)->load('providerAdapter');
    }

    private function host(?string $url): ?string
    {
        if (! $url) return null;
        $host = parse_url($url, PHP_URL_HOST);
        return $host ? strtolower(preg_replace('/^www\./i', '', $host)) : null;
    }
}
