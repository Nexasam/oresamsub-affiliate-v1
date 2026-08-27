<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentAdmin\SaveProviderConnectionRequest;
use App\Http\Requests\PlatformAdmin\PromoteLegacyProviderConfigurationRequest;
use App\Http\Requests\PlatformAdmin\ReviewParentProviderConnectionRequest;
use App\Models\ParentProviderConnection;
use App\Services\PlatformAdmin\LegacyProviderConfigurationPromotionService;
use App\Services\PlatformAdmin\ParentConnectionApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ParentProviderConnectionController extends Controller
{
    public function index(): View
    {
        return view('platform-admin.provider-connections.index');
    }

    public function data(): JsonResponse
    {
        $connections = ParentProviderConnection::query()
            ->with(['parentBusiness:id,name,slug', 'providerAdapter:id,name,slug,adapter_key,capabilities,settings,status', 'providerConnection:id,provider_adapter_id,name,slug,adapter,capabilities,base_url,settings,status'])
            ->orderByRaw("CASE approval_status WHEN 'pending' THEN 0 WHEN 'rejected' THEN 1 ELSE 2 END")
            ->latest('submitted_at')
            ->get()
            ->map(fn (ParentProviderConnection $connection) => $this->present($connection));

        return response()->json([
            'connections' => $connections,
            'counts' => $connections->countBy('approval_status'),
        ]);
    }

    public function review(ReviewParentProviderConnectionRequest $request, ParentProviderConnection $connection, ParentConnectionApprovalService $approvals): JsonResponse
    {
        if ($request->validated('action') === 'approve') {
            $connection = $approvals->approve($connection, $request->user('platform_admin'));

            return response()->json(['message' => 'Provider connection approved.', 'connection' => $this->present($connection)]);
        }

        $connection = DB::transaction(function () use ($request, $connection) {
            $connection = ParentProviderConnection::query()->lockForUpdate()->findOrFail($connection->id);

            if ($connection->approval_status !== 'pending') {
                throw ValidationException::withMessages(['action' => 'Only a pending connection can be reviewed.']);
            }

            $connection->update([
                'approval_status' => 'rejected',
                'approved_at' => null,
                'approved_by_admin_id' => $request->user('platform_admin')->id,
                'rejection_reason' => $request->validated('reason'),
            ]);

            return $connection->fresh(['parentBusiness:id,name,slug', 'providerAdapter:id,name,slug,adapter_key,capabilities,settings,status', 'providerConnection:id,provider_adapter_id,name,slug,adapter,capabilities,base_url,settings,status']);
        });

        return response()->json([
            'message' => $connection->approval_status === 'approved' ? 'Provider connection approved.' : 'Provider connection rejected.',
            'connection' => $this->present($connection),
        ]);
    }

    public function promoteLegacyConfiguration(
        PromoteLegacyProviderConfigurationRequest $request,
        ParentProviderConnection $connection,
        LegacyProviderConfigurationPromotionService $promotions,
    ): JsonResponse {
        $result = $promotions->promote(
            $connection,
            $request->user('platform_admin'),
            $request->boolean('promote_to_adapter'),
        );

        return response()->json([
            'message' => $result['strategy'] === 'cloned'
                ? 'Legacy configuration promoted into a safe dedicated connection.'
                : 'Legacy configuration promoted into the shared connection.',
            'promotion' => Arr::only($result, ['strategy', 'adapter_created']),
            'connection' => $this->present($result['connection']),
        ]);
    }

    private function present(ParentProviderConnection $connection): array
    {
        [$effectiveSettings, $configurationSource] = $this->effectiveConfiguration($connection);

        return [
            'id' => $connection->id,
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
            'settings' => $this->redactedSettings($effectiveSettings),
            'configuration_source' => $configurationSource,
            'parent_business' => $connection->parentBusiness,
            'provider_connection' => $connection->providerConnection,
            'provider_adapter' => $connection->providerAdapter,
            'credential_status' => collect(data_get($connection->providerConnection?->capabilities, 'credential_fields')
                ?? data_get($connection->providerAdapter?->capabilities, 'credential_fields')
                ?? SaveProviderConnectionRequest::CREDENTIAL_FIELDS)
                ->mapWithKeys(fn ($key) => [$key => filled(($connection->credentials ?? [])[$key] ?? null)])->all(),
            'legacy_promotion' => [
                'available' => $this->hasTechnicalSettings($connection->settings ?? [])
                    && ! DB::table('provider_configuration_promotions')->where('parent_provider_connection_id', $connection->id)->exists(),
                'shared_has_configuration' => filled($connection->providerConnection?->settings),
                'shared_parent_count' => $connection->providerConnection?->parentConnections()->count() ?? 0,
                'will_clone' => ($connection->providerConnection?->parentConnections()->whereKeyNot($connection->id)->exists() ?? false)
                    || (filled($connection->providerConnection?->settings)
                        && $this->canonicalSettings($connection->providerConnection?->settings) !== $this->canonicalSettings(Arr::except($connection->settings ?? [], ['is_primary']))),
            ],
        ];
    }

    private function effectiveConfiguration(ParentProviderConnection $connection): array
    {
        $parentSettings = Arr::except($connection->settings ?? [], ['is_primary']);
        if ($parentSettings !== []) {
            return [$parentSettings, 'parent'];
        }

        if (filled($connection->providerConnection?->settings)) {
            return [$connection->providerConnection->settings, 'connection'];
        }

        if (filled($connection->providerAdapter?->settings)) {
            return [$connection->providerAdapter->settings, 'adapter'];
        }

        return [[], 'none'];
    }

    private function hasTechnicalSettings(array $settings): bool
    {
        return Arr::except($settings, ['is_primary']) !== [];
    }

    private function canonicalSettings(?array $settings): string
    {
        $settings ??= [];
        ksort($settings);

        return json_encode($settings);
    }

    private function redactedSettings(array $settings): array
    {
        $settings['request_headers'] = $this->redactedHeaders($settings['request_headers'] ?? []);

        foreach ($settings['product_configs'] ?? [] as $product => $config) {
            $settings['product_configs'][$product]['request_headers'] = $this->redactedHeaders($config['request_headers'] ?? []);
        }

        return $settings;
    }

    private function redactedHeaders(array $headers): array
    {
        return collect($headers)->map(function ($header) {
            if (strtolower((string) ($header['key'] ?? '')) === 'authorization' && ($header['type'] ?? null) === 'literal') {
                $header['value'] = '[redacted]';
            }

            return $header;
        })->all();
    }
}
