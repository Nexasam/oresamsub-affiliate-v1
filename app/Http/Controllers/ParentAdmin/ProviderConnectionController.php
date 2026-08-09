<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentAdmin\SaveProviderConnectionRequest;
use App\Models\ParentProviderConnection;
use App\Models\ProviderConnection;
use App\Services\ParentAdmin\ProviderConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderConnectionController extends Controller
{
    public function __construct(private readonly ProviderConnectionService $connections) {}

    public function index(): View
    {
        return view('parent-admin.provider-connections.index');
    }

    public function data(Request $request): JsonResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        $connections = $parent->providerConnections()->with('providerConnection')->latest()->get()
            ->map(fn ($connection) => $this->connections->present($connection));

        return response()->json([
            'connections' => $connections,
            'adapters' => ProviderConnection::where('status', 'active')->orderBy('name')->get(),
            'runtime_fields' => SaveProviderConnectionRequest::RUNTIME_FIELDS,
            'credential_fields' => SaveProviderConnectionRequest::CREDENTIAL_FIELDS,
        ]);
    }

    public function store(SaveProviderConnectionRequest $request): JsonResponse
    {
        $connection = $this->connections->save($request->user('parent_admin')->parentBusiness, $request->validated());

        return response()->json(['message' => 'Provider connection created.', 'connection' => $this->connections->present($connection)], 201);
    }

    public function update(SaveProviderConnectionRequest $request, ParentProviderConnection $connection): JsonResponse
    {
        $connection = $this->connections->save($request->user('parent_admin')->parentBusiness, $request->validated(), $connection);

        return response()->json(['message' => 'Provider connection updated.', 'connection' => $this->connections->present($connection)]);
    }
}
