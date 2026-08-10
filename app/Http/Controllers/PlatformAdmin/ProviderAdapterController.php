<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformAdmin\SaveProviderAdapterRequest;
use App\Models\ProviderConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ProviderAdapterController extends Controller
{
    public function index(): View
    {
        return view('platform-admin.provider-adapters.index');
    }

    public function data(): JsonResponse
    {
        return response()->json([
            'adapters' => ProviderConnection::query()->withCount('parentConnections')->orderBy('name')->get(),
            'allowed' => [
                'services' => SaveProviderAdapterRequest::SERVICES,
                'methods' => SaveProviderAdapterRequest::METHODS,
                'credential_fields' => SaveProviderAdapterRequest::CREDENTIAL_FIELDS,
            ],
        ]);
    }

    public function store(SaveProviderAdapterRequest $request): JsonResponse
    {
        $adapter = ProviderConnection::create($request->validated());

        return response()->json(['message' => 'Provider adapter created.', 'adapter' => $adapter], 201);
    }

    public function update(SaveProviderAdapterRequest $request, ProviderConnection $providerAdapter): JsonResponse
    {
        $providerAdapter->update($request->validated());

        return response()->json(['message' => 'Provider adapter updated.', 'adapter' => $providerAdapter->fresh()]);
    }
}
