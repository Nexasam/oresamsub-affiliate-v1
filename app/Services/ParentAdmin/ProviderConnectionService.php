<?php

namespace App\Services\ParentAdmin;

use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProviderConnectionService
{
    public function save(ParentBusiness $parent, array $data, ?ParentProviderConnection $connection = null): ParentProviderConnection
    {
        if ($connection) {
            abort_unless($connection->parent_business_id === $parent->id, 404);
        }
        $duplicate = $parent->providerConnections()
            ->where('provider_connection_id', $data['provider_connection_id'])
            ->where('name', $data['name'])
            ->when($connection, fn ($query) => $query->where('id', '!=', $connection->id))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'This parent already has a connection with that adapter and name.']);
        }

        return DB::transaction(function () use ($parent, $data, $connection) {
            $settings = $data['settings'];
            $settings['is_primary'] = (bool) $data['is_primary'];
            if ($settings['is_primary']) {
                $parent->providerConnections()->lockForUpdate()->get()->each(function ($existing) {
                    $existingSettings = $existing->settings ?? [];
                    $existingSettings['is_primary'] = false;
                    $existing->update(['settings' => $existingSettings]);
                });
            }

            $credentials = $connection?->credentials ?? [];
            foreach ($data['credentials'] ?? [] as $key => $value) {
                if (filled($value)) {
                    $credentials[$key] = $value;
                }
            }

            $attributes = [
                'provider_connection_id' => $data['provider_connection_id'],
                'name' => $data['name'],
                'base_url' => $data['base_url'] ?? null,
                'credentials' => $credentials ?: null,
                'settings' => $settings,
                'status' => $data['status'],
            ];

            if ($connection) {
                $connection->update($attributes);
            } else {
                $connection = $parent->providerConnections()->create($attributes);
            }

            return $connection->fresh('providerConnection:id,name,slug,adapter,capabilities,status');
        });
    }

    public function present(ParentProviderConnection $connection): array
    {
        return [
            'id' => $connection->id,
            'provider_connection_id' => $connection->provider_connection_id,
            'name' => $connection->name,
            'base_url' => $connection->base_url,
            'status' => $connection->status,
            'last_tested_at' => $connection->last_tested_at,
            'settings' => $connection->settings ?? [],
            'provider_connection' => $connection->providerConnection,
            'credential_status' => collect(['api_public_key', 'api_secret_key', 'api_password'])
                ->mapWithKeys(fn ($key) => [$key => filled(($connection->credentials ?? [])[$key] ?? null)])->all(),
        ];
    }
}
