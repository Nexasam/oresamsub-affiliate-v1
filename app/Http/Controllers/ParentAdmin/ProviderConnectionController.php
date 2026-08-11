<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentAdmin\SaveProviderConnectionRequest;
use App\Models\ParentProviderConnection;
use App\Models\ProviderConnection;
use App\Services\ParentAdmin\ProviderConnectionService;
use App\Support\ProviderProductRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderConnectionController extends Controller
{
    public function __construct(
        private readonly ProviderConnectionService $connections,
        private readonly ProviderProductRegistry $products,
    ) {}

    public function index(Request $request): View
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        $connections = $parent->providerConnections()->with('providerConnection')->latest()->get()
            ->map(fn ($connection) => $this->connections->present($connection));
        $editingConnection = null;

        if ($request->filled('edit')) {
            $editingConnection = $parent->providerConnections()
                ->with('providerConnection')
                ->findOrFail($request->integer('edit'));
        }

        $adapters = ProviderConnection::where('status', 'active')->orderBy('name')->get()
            ->each(fn (ProviderConnection $adapter) => $adapter->capabilities = $this->products->normalizeCapabilities($adapter->capabilities));

        if ($editingConnection && $editingConnection->providerConnection?->status === 'inactive') {
            $inactiveAdapter = $editingConnection->providerConnection;
            $inactiveAdapter->capabilities = $this->products->normalizeCapabilities($inactiveAdapter->capabilities);
            $adapters->push($inactiveAdapter);
        }

        return view('parent-admin.provider-connections.index', [
            'connections' => $connections,
            'adapters' => $adapters->unique('id')->values(),
            'products' => $this->products->products(),
            'runtimeFields' => SaveProviderConnectionRequest::RUNTIME_FIELDS,
            'credentialFields' => SaveProviderConnectionRequest::CREDENTIAL_FIELDS,
            'editingConnection' => $editingConnection ? $this->connections->present($editingConnection) : null,
            'showForm' => $request->boolean('create') || $editingConnection !== null || session()->has('errors'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        $connections = $parent->providerConnections()->with('providerConnection')->latest()->get()
            ->map(fn ($connection) => $this->connections->present($connection));

        $adapters = ProviderConnection::where('status', 'active')->orderBy('name')->get()
            ->each(fn (ProviderConnection $adapter) => $adapter->capabilities = $this->products->normalizeCapabilities($adapter->capabilities));

        return response()->json([
            'connections' => $connections,
            'adapters' => $adapters,
            'runtime_fields' => SaveProviderConnectionRequest::RUNTIME_FIELDS,
            'credential_fields' => SaveProviderConnectionRequest::CREDENTIAL_FIELDS,
            'products' => $this->products->products(),
        ]);
    }

    public function store(SaveProviderConnectionRequest $request): JsonResponse|RedirectResponse
    {
        $connection = $this->connections->save($request->user('parent_admin')->parentBusiness, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Provider connection created.', 'connection' => $this->connections->present($connection)], 201);
        }

        return redirect()->route('parent-admin.provider-connections.index')
            ->with('success', 'Provider connection created and submitted for platform approval.');
    }

    public function update(SaveProviderConnectionRequest $request, ParentProviderConnection $connection): JsonResponse|RedirectResponse
    {
        $connection = $this->connections->save($request->user('parent_admin')->parentBusiness, $request->validated(), $connection);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Provider connection updated.', 'connection' => $this->connections->present($connection)]);
        }

        return redirect()->route('parent-admin.provider-connections.index')
            ->with('success', 'Provider connection updated. Sensitive changes were returned for platform approval.');
    }
}
