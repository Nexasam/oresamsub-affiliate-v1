<?php

namespace App\Services\PlatformAdmin;

use App\Models\Admin;
use App\Models\ParentProviderConnection;
use App\Models\ProviderAdapter;
use App\Models\ProviderConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LegacyProviderConfigurationPromotionService
{
    public function promote(ParentProviderConnection $source, Admin $reviewer, bool $promoteToAdapter): array
    {
        return DB::transaction(function () use ($source, $reviewer, $promoteToAdapter): array {
            $source = ParentProviderConnection::query()->with(['providerConnection', 'providerAdapter'])->lockForUpdate()->findOrFail($source->id);
            $technicalSettings = $this->technicalSettings($source->settings ?? []);
            if ($technicalSettings === []) {
                throw ValidationException::withMessages(['connection' => 'This parent connection has no legacy technical configuration to promote.']);
            }

            $original = $source->providerConnection;
            $targetBefore = $this->snapshot($original);
            [$target, $strategy] = $this->target($source, $technicalSettings);

            $adapter = null;
            $adapterCreated = false;
            if ($promoteToAdapter) {
                [$adapter, $adapterCreated] = $this->resolveAdapter($target, $technicalSettings);
            }

            $target->update([
                'provider_adapter_id' => $adapter?->id ?? $target->provider_adapter_id,
                'adapter' => $adapter?->adapter_key ?? $target->adapter,
                'capabilities' => $adapter?->capabilities ?? $target->capabilities,
                'base_url' => $source->base_url ?: $target->base_url,
                'settings' => $technicalSettings,
                'adapter_version' => $adapter?->version ?? $target->adapter_version,
            ]);

            $source->update([
                'provider_connection_id' => $target->id,
                'provider_adapter_id' => $adapter?->id ?? $target->provider_adapter_id,
                'settings' => Arr::only($source->settings ?? [], ['is_primary']),
            ]);

            DB::table('provider_configuration_promotions')->insert([
                'parent_provider_connection_id' => $source->id,
                'source_provider_connection_id' => $original?->id,
                'target_provider_connection_id' => $target->id,
                'provider_adapter_id' => $adapter?->id,
                'promoted_by_admin_id' => $reviewer->id,
                'strategy' => $strategy,
                'source_snapshot' => json_encode(['base_url' => $source->base_url, 'settings' => $technicalSettings]),
                'target_before_snapshot' => $targetBefore ? json_encode($targetBefore) : null,
                'target_after_snapshot' => json_encode($this->snapshot($target->fresh())),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'connection' => $source->fresh(['parentBusiness:id,name,slug', 'providerAdapter:id,name,slug,adapter_key,capabilities,settings,status', 'providerConnection:id,provider_adapter_id,name,slug,adapter,capabilities,base_url,settings,status']),
                'strategy' => $strategy,
                'adapter_created' => $adapterCreated,
            ];
        });
    }

    private function target(ParentProviderConnection $source, array $settings): array
    {
        $shared = $source->providerConnection;
        if (! $shared) {
            return [$this->cloneConnection(null, $source), 'created'];
        }

        $otherParentsUseIt = $shared->parentConnections()->whereKeyNot($source->id)->exists();
        $hasDifferentConfiguration = filled($shared->settings) && $this->canonical($shared->settings) !== $this->canonical($settings);
        if ($otherParentsUseIt || $hasDifferentConfiguration) {
            return [$this->cloneConnection($shared, $source), 'cloned'];
        }

        return [$shared, 'updated_in_place'];
    }

    private function cloneConnection(?ProviderConnection $shared, ParentProviderConnection $source): ProviderConnection
    {
        $name = ($shared?->name ?: $source->name ?: 'Provider').' · '.$source->parentBusiness->name;

        return ProviderConnection::create([
            'provider_adapter_id' => $shared?->provider_adapter_id,
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'adapter' => $shared?->adapter ?: 'configurable_http',
            'capabilities' => $shared?->capabilities,
            'base_url' => $source->base_url ?: $shared?->base_url,
            'website_url' => $shared?->website_url,
            'documentation_url' => $shared?->documentation_url,
            'settings' => null,
            'adapter_version' => $shared?->adapter_version,
            'status' => $shared?->status ?: 'active',
        ]);
    }

    private function resolveAdapter(ProviderConnection $target, array $settings): array
    {
        $generic = $this->genericSettings($settings);
        $existing = ProviderAdapter::query()->where('status', 'active')->get()
            ->first(fn (ProviderAdapter $adapter) => $this->canonical($this->genericSettings($adapter->settings ?? [])) === $this->canonical($generic));

        if ($existing) {
            return [$existing, false];
        }

        $name = $target->name.' Adapter';
        $adapter = ProviderAdapter::create([
            'name' => $name,
            'slug' => $this->uniqueAdapterSlug($name),
            'adapter_key' => $this->uniqueAdapterKey($name),
            'capabilities' => $target->capabilities,
            'settings' => $generic ?: null,
            'version' => 1,
            'status' => 'active',
        ]);

        return [$adapter, true];
    }

    private function technicalSettings(array $settings): array
    {
        return Arr::except($settings, ['is_primary']);
    }

    private function genericSettings(array $settings): array
    {
        return collect($settings)->reject(function ($value, $key): bool {
            $key = strtolower((string) $key);

            return $key === 'endpoints' || str_ends_with($key, '_url') || in_array($key, ['base_url', 'endpoint_url'], true);
        })->map(fn ($value) => is_array($value) ? $this->genericSettings($value) : $value)->all();
    }

    private function canonical(?array $value): string
    {
        $value = $this->sortRecursive($value ?? []);

        return json_encode($value);
    }

    private function sortRecursive(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as &$item) {
            if (is_array($item)) {
                $item = $this->sortRecursive($item);
            }
        }

        return $value;
    }

    private function snapshot(?ProviderConnection $connection): ?array
    {
        if (! $connection) {
            return null;
        }

        return ['id' => $connection->id, 'provider_adapter_id' => $connection->provider_adapter_id, 'base_url' => $connection->base_url, 'settings' => $connection->settings];
    }

    private function uniqueSlug(string $name): string
    {
        return $this->uniqueValue(Str::slug($name) ?: 'provider', fn ($value) => ProviderConnection::where('slug', $value)->exists());
    }

    private function uniqueAdapterSlug(string $name): string
    {
        return $this->uniqueValue(Str::slug($name) ?: 'adapter', fn ($value) => ProviderAdapter::where('slug', $value)->exists());
    }

    private function uniqueAdapterKey(string $name): string
    {
        return $this->uniqueValue(Str::snake($name) ?: 'adapter', fn ($value) => ProviderAdapter::where('adapter_key', $value)->exists());
    }

    private function uniqueValue(string $base, callable $exists): string
    {
        $value = $base;
        $suffix = 2;
        while ($exists($value)) {
            $value = $base.'_'.$suffix++;
        }

        return $value;
    }
}
