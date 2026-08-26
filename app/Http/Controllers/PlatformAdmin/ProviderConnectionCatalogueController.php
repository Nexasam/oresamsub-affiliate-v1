<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformAdmin\SaveProviderConnectionCatalogueRequest;
use App\Models\ProviderAdapter;
use App\Models\ProviderConnection;
use App\Services\PlatformAdmin\ProviderConnectionCatalogueService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ProviderConnectionCatalogueController extends Controller
{
    public function __construct(private readonly ProviderConnectionCatalogueService $catalogue) {}

    public function index(): View
    {
        return view('platform-admin.provider-connections.catalogue', [
            'adapters' => ProviderAdapter::where('status', 'active')->orderBy('name')->get(),
            'connections' => ProviderConnection::with('providerAdapter')->orderBy('name')->get(),
        ]);
    }

    public function store(SaveProviderConnectionCatalogueRequest $request): JsonResponse
    {
        $connection = $this->catalogue->save($request->validated());
        return response()->json(['message' => 'Provider connection created.', 'connection' => $connection], 201);
    }

    public function update(SaveProviderConnectionCatalogueRequest $request, ProviderConnection $providerConnection): JsonResponse
    {
        return response()->json(['message' => 'Provider connection updated.', 'connection' => $this->catalogue->save($request->validated(), $providerConnection)]);
    }
}
