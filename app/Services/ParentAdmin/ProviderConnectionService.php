<?php

namespace App\Services\ParentAdmin;

use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Support\ProviderProductRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProviderConnectionService
{
    public function __construct(private readonly ProviderProductRegistry $products) {}

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
            $existingConnection = $connection;
            $settings = $data['settings'];
            $settings = $this->synchronizeSharedResponseDefaults($settings);
            $settings['is_primary'] = (bool) $data['is_primary'];

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

            $requiresApproval = ! $existingConnection
                || $existingConnection->approval_status === 'rejected'
                || $this->sensitiveConfigurationChanged($existingConnection, $attributes, $data['credentials'] ?? []);

            if ($requiresApproval) {
                $attributes += [
                    'approval_status' => 'pending',
                    'submitted_at' => now(),
                    'approved_at' => null,
                    'approved_by_admin_id' => null,
                    'rejection_reason' => null,
                ];
            }

            if (! $requiresApproval && $settings['is_primary']) {
                $parent->providerConnections()->lockForUpdate()->get()->each(function ($existing) {
                    $existingSettings = $existing->settings ?? [];
                    $existingSettings['is_primary'] = false;
                    $existing->update(['settings' => $existingSettings]);
                });
            }

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
            'approval_status' => $connection->approval_status,
            'submitted_at' => $connection->submitted_at,
            'approved_at' => $connection->approved_at,
            'rejection_reason' => $connection->rejection_reason,
            'last_tested_at' => $connection->last_tested_at,
            'settings' => $connection->settings ?? [],
            'provider_connection' => $connection->providerConnection ? [
                ...$connection->providerConnection->toArray(),
                'capabilities' => $this->products->normalizeCapabilities($connection->providerConnection->capabilities),
            ] : null,
            'credential_status' => collect(['api_public_key', 'api_secret_key', 'api_password'])
                ->mapWithKeys(fn ($key) => [$key => filled(($connection->credentials ?? [])[$key] ?? null)])->all(),
        ];
    }

    private function sensitiveConfigurationChanged(?ParentProviderConnection $connection, array $attributes, array $submittedCredentials): bool
    {
        if (! $connection) {
            return true;
        }

        if ((int) $connection->provider_connection_id !== (int) $attributes['provider_connection_id']
            || (string) $connection->base_url !== (string) $attributes['base_url']) {
            return true;
        }

        $currentSettings = $connection->settings ?? [];
        $submittedSettings = $attributes['settings'] ?? [];
        unset($currentSettings['is_primary'], $submittedSettings['is_primary']);

        if ($this->canonicalize($currentSettings) !== $this->canonicalize($submittedSettings)) {
            return true;
        }

        $savedCredentials = $connection->credentials ?? [];

        foreach ($submittedCredentials as $key => $value) {
            if (filled($value) && ($savedCredentials[$key] ?? null) !== $value) {
                return true;
            }
        }

        return false;
    }

    private function canonicalize(array $value): array
    {
        ksort($value);

        foreach ($value as &$item) {
            if (is_array($item)) {
                $item = $this->canonicalize($item);
            }
        }

        return $value;
    }

    private function synchronizeSharedResponseDefaults(array $settings): array
    {
        $dataConfig = $settings['product_configs']['data'] ?? null;
        if (! is_array($dataConfig)) {
            return $settings;
        }

        foreach (['success_conditions', 'success_message_path', 'failure_message_path', 'expected_success_code', 'expected_failure_code'] as $field) {
            if (array_key_exists($field, $dataConfig)) {
                $settings[$field] = $dataConfig[$field];
            }
        }

        return $settings;
    }
}
