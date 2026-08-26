<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformAdmin\SaveProviderAdapterRequest;
use App\Models\ProviderAdapter;
use App\Support\ProviderProductRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ProviderAdapterController extends Controller
{
    public function __construct(private readonly ProviderProductRegistry $products) {}

    public function index(): View
    {
        return view('platform-admin.provider-adapters.index');
    }

    public function data(): JsonResponse
    {
        return response()->json([
            'adapters' => ProviderAdapter::query()->withCount('connections')->orderBy('name')->get()
                ->each(function (ProviderAdapter $adapter) {
                    $adapter->capabilities = $this->products->normalizeCapabilities($adapter->capabilities);
                    $adapter->setAttribute('adapter', $adapter->adapter_key);
                    $adapter->setAttribute('parent_connections_count', $adapter->connections_count);
                }),
            'allowed' => [
                'services' => $this->products->products(),
                'methods' => SaveProviderAdapterRequest::METHODS,
                'credential_fields' => SaveProviderAdapterRequest::CREDENTIAL_FIELDS,
            ],
        ]);
    }

    public function store(SaveProviderAdapterRequest $request): JsonResponse
    {
        $adapter = ProviderAdapter::create($request->validated());

        return response()->json(['message' => 'Provider adapter created.', 'adapter' => $adapter], 201);
    }

    public function update(SaveProviderAdapterRequest $request, ProviderAdapter $providerAdapter): JsonResponse
    {
        $providerAdapter->update([...$request->validated(), 'version' => $providerAdapter->version + 1]);

        return response()->json(['message' => 'Provider adapter updated.', 'adapter' => $providerAdapter->fresh()]);
    }
}
