<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformAdmin\ReviewParentProviderConnectionRequest;
use App\Models\ParentProviderConnection;
use Illuminate\Http\JsonResponse;
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
            ->with(['parentBusiness:id,name,slug', 'providerConnection:id,name,slug,adapter,capabilities,status'])
            ->orderByRaw("CASE approval_status WHEN 'pending' THEN 0 WHEN 'rejected' THEN 1 ELSE 2 END")
            ->latest('submitted_at')
            ->get()
            ->map(fn (ParentProviderConnection $connection) => $this->present($connection));

        return response()->json([
            'connections' => $connections,
            'counts' => $connections->countBy('approval_status'),
        ]);
    }

    public function review(ReviewParentProviderConnectionRequest $request, ParentProviderConnection $connection): JsonResponse
    {
        $connection = DB::transaction(function () use ($request, $connection) {
            $connection = ParentProviderConnection::query()->lockForUpdate()->findOrFail($connection->id);

            if ($connection->approval_status !== 'pending') {
                throw ValidationException::withMessages(['action' => 'Only a pending connection can be reviewed.']);
            }

            if ($request->validated('action') === 'approve') {
                if (($connection->settings['is_primary'] ?? false) === true) {
                    $connection->parentBusiness->providerConnections()
                        ->where('id', '!=', $connection->id)
                        ->lockForUpdate()
                        ->get()
                        ->each(function (ParentProviderConnection $existing) {
                            $settings = $existing->settings ?? [];
                            $settings['is_primary'] = false;
                            $existing->update(['settings' => $settings]);
                        });
                }

                $connection->update([
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                    'approved_by_admin_id' => $request->user('platform_admin')->id,
                    'rejection_reason' => null,
                ]);
            } else {
                $connection->update([
                    'approval_status' => 'rejected',
                    'approved_at' => null,
                    'approved_by_admin_id' => $request->user('platform_admin')->id,
                    'rejection_reason' => $request->validated('reason'),
                ]);
            }

            return $connection->fresh(['parentBusiness:id,name,slug', 'providerConnection:id,name,slug,adapter,capabilities,status']);
        });

        return response()->json([
            'message' => $connection->approval_status === 'approved' ? 'Provider connection approved.' : 'Provider connection rejected.',
            'connection' => $this->present($connection),
        ]);
    }

    private function present(ParentProviderConnection $connection): array
    {
        return [
            'id' => $connection->id,
            'name' => $connection->name,
            'base_url' => $connection->base_url,
            'status' => $connection->status,
            'approval_status' => $connection->approval_status,
            'submitted_at' => $connection->submitted_at,
            'approved_at' => $connection->approved_at,
            'rejection_reason' => $connection->rejection_reason,
            'settings' => $this->redactedSettings($connection->settings ?? []),
            'parent_business' => $connection->parentBusiness,
            'provider_connection' => $connection->providerConnection,
            'credential_status' => collect(['api_public_key', 'api_secret_key', 'api_password'])
                ->mapWithKeys(fn ($key) => [$key => filled(($connection->credentials ?? [])[$key] ?? null)])->all(),
        ];
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
