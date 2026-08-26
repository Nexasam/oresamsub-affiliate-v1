<?php

namespace App\Services\PlatformAdmin;

use App\Models\Admin;
use App\Models\ParentProviderConnection;
use App\Models\ProviderConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ParentConnectionApprovalService
{
    public function approve(ParentProviderConnection $parentConnection, Admin $reviewer): ParentProviderConnection
    {
        return DB::transaction(function () use ($parentConnection, $reviewer): ParentProviderConnection {
            $parentConnection = ParentProviderConnection::query()->with('providerAdapter')->lockForUpdate()->findOrFail($parentConnection->id);
            if ($parentConnection->approval_status !== 'pending') {
                throw ValidationException::withMessages(['action' => 'Only a pending connection can be reviewed.']);
            }

            if ($parentConnection->request_type === 'discovery' && ! $parentConnection->provider_connection_id) {
                $adapter = $parentConnection->providerAdapter;
                if (! $adapter || $adapter->status !== 'active') {
                    throw ValidationException::withMessages(['action' => 'The selected provider adapter is unavailable.']);
                }
                $host = $this->host($parentConnection->proposed_base_url);
                $provider = ProviderConnection::query()->where('provider_adapter_id', $adapter->id)->lockForUpdate()->get()
                    ->first(fn ($candidate) => $host && $this->host($candidate->website_url ?: $candidate->base_url) === $host);
                if (! $provider) {
                    $name = trim((string) $parentConnection->proposed_provider_name);
                    $provider = ProviderConnection::create([
                        'provider_adapter_id' => $adapter->id,
                        'name' => $name,
                        'slug' => $this->uniqueSlug($name),
                        'adapter' => $adapter->adapter_key,
                        'capabilities' => $adapter->capabilities,
                        'base_url' => $parentConnection->proposed_base_url,
                        'website_url' => $this->origin($parentConnection->proposed_base_url),
                        'documentation_url' => $parentConnection->proposed_documentation_url,
                        'settings' => $adapter->settings,
                        'adapter_version' => $adapter->version,
                        'status' => 'active',
                    ]);
                }
                $parentConnection->provider_connection_id = $provider->id;
            }

            if (($parentConnection->settings['is_primary'] ?? false) === true) {
                $parentConnection->parentBusiness->providerConnections()->whereKeyNot($parentConnection->id)->lockForUpdate()->get()
                    ->each(function ($existing): void {
                        $settings = $existing->settings ?? [];
                        $settings['is_primary'] = false;
                        $existing->update(['settings' => $settings]);
                    });
            }

            $parentConnection->fill(['approval_status' => 'approved', 'approved_at' => now(), 'approved_by_admin_id' => $reviewer->id, 'rejection_reason' => null])->save();

            return $parentConnection->fresh(['parentBusiness:id,name,slug', 'providerAdapter:id,name,slug,adapter_key,capabilities,status', 'providerConnection:id,provider_adapter_id,name,slug,adapter,capabilities,status']);
        });
    }

    private function host(?string $url): ?string
    {
        $host = $url ? parse_url($url, PHP_URL_HOST) : null;
        return $host ? strtolower(preg_replace('/^www\./i', '', $host)) : null;
    }

    private function origin(?string $url): ?string
    {
        if (! $url) return null;
        $parts = parse_url($url);
        return isset($parts['scheme'], $parts['host']) ? $parts['scheme'].'://'.$parts['host'] : null;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'provider';
        $slug = $base;
        $suffix = 2;
        while (ProviderConnection::where('slug', $slug)->exists()) $slug = $base.'-'.$suffix++;
        return $slug;
    }
}
