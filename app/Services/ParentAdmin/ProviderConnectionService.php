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
            $submittedProviderId = isset($data['provider_connection_id']) ? (int) $data['provider_connection_id'] : null;
            $submittedAdapterId = isset($data['provider_adapter_id'])
                ? (int) $data['provider_adapter_id']
                : \App\Models\ProviderConnection::find($submittedProviderId)?->provider_adapter_id;

            if ($submittedProviderId !== ($connection->provider_connection_id ? (int) $connection->provider_connection_id : null)
                || ($connection->provider_adapter_id && (int) $submittedAdapterId !== (int) $connection->provider_adapter_id)) {
                throw ValidationException::withMessages([
                    'provider_connection_id' => "A saved connection's adapter and provider cannot be changed. Create a new connection instead.",
                ]);
            }
        }
        $duplicate = $parent->providerConnections()
            ->when(isset($data['provider_connection_id']), fn ($query) => $query->where('provider_connection_id', $data['provider_connection_id']))
            ->when(! isset($data['provider_connection_id']), fn ($query) => $query->whereNull('provider_connection_id')->where('proposed_base_url', $data['proposed_base_url'] ?? null))
            ->where('name', $data['name'])
            ->when($connection, fn ($query) => $query->where('id', '!=', $connection->id))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'This parent already has a connection with that adapter and name.']);
        }

        return DB::transaction(function () use ($parent, $data, $connection) {
            $existingConnection = $connection;
            $settings = $data['settings'] ?? ($connection?->settings ?? []);
            if (isset($data['settings'])) {
                $settings = $this->synchronizeSharedResponseDefaults($settings);
            }
            $settings['is_primary'] = (bool) $data['is_primary'];

            $credentials = $connection?->credentials ?? [];
            foreach ($data['credentials'] ?? [] as $key => $value) {
                if (filled($value)) {
                    $credentials[$key] = $value;
                }
            }

            $attributes = [
                'provider_adapter_id' => $data['provider_adapter_id']
                    ?? \App\Models\ProviderConnection::find($data['provider_connection_id'] ?? null)?->provider_adapter_id
                    ?? $connection?->provider_adapter_id,
                'provider_connection_id' => $data['provider_connection_id'] ?? null,
                'request_type' => filled($data['provider_connection_id'] ?? null) ? 'existing' : 'discovery',
                'name' => $data['name'],
                'base_url' => $data['base_url'] ?? null,
                'proposed_provider_name' => $data['proposed_provider_name'] ?? null,
                'proposed_base_url' => $data['proposed_base_url'] ?? null,
                'proposed_documentation_url' => $data['proposed_documentation_url'] ?? null,
                'discovery_notes' => $data['discovery_notes'] ?? null,
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

            return $connection->fresh(['providerConnection:id,provider_adapter_id,name,slug,adapter,capabilities,status', 'providerAdapter:id,name,slug,adapter_key,capabilities,status']);
        });
    }

    public function present(ParentProviderConnection $connection): array
    {
        return [
            'id' => $connection->id,
            'provider_connection_id' => $connection->provider_connection_id,
            'provider_adapter_id' => $connection->provider_adapter_id,
            'request_type' => $connection->request_type,
            'name' => $connection->name,
            'base_url' => $connection->base_url,
            'proposed_provider_name' => $connection->proposed_provider_name,
            'proposed_base_url' => $connection->proposed_base_url,
            'proposed_documentation_url' => $connection->proposed_documentation_url,
            'discovery_notes' => $connection->discovery_notes,
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
            'provider_adapter' => $connection->providerAdapter,
            'credential_status' => collect(data_get($connection->providerConnection?->capabilities, 'credential_fields')
                ?? data_get($connection->providerAdapter?->capabilities, 'credential_fields')
                ?? \App\Http\Requests\ParentAdmin\SaveProviderConnectionRequest::CREDENTIAL_FIELDS)
                ->mapWithKeys(fn ($key) => [$key => filled(($connection->credentials ?? [])[$key] ?? null)])->all(),
        ];
    }

    private function sensitiveConfigurationChanged(?ParentProviderConnection $connection, array $attributes, array $submittedCredentials): bool
    {
        if (! $connection) {
            return true;
        }

        if ((int) $connection->provider_connection_id !== (int) $attributes['provider_connection_id']
            || (int) $connection->provider_adapter_id !== (int) $attributes['provider_adapter_id']
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
