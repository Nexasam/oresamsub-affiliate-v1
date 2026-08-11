<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformAdmin\StoreParentBusinessRequest;
use App\Models\ParentBusiness;
use App\Services\PlatformAdmin\ParentBusinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ParentBusinessController extends Controller
{
    public function __construct(private readonly ParentBusinessService $parents) {}

    public function index(): View
    {
        return view('platform-admin.parent-businesses.index');
    }

    public function data(): JsonResponse
    {
        $parents = ParentBusiness::query()
            ->with(['parentAdmins' => fn ($query) => $query->orderBy('id')])
            ->withCount(['affiliates', 'providerConnections', 'resellerLevels'])
            ->latest()
            ->get()
            ->map(fn (ParentBusiness $parent) => $this->parents->present($parent));

        return response()->json([
            'parents' => $parents,
            'summary' => [
                'total' => $parents->count(),
                'active' => $parents->where('status', 'active')->count(),
                'affiliates' => $parents->sum('affiliate_count'),
                'connections' => $parents->sum('provider_connection_count'),
            ],
        ]);
    }

    public function store(StoreParentBusinessRequest $request): JsonResponse
    {
        $parent = $this->parents->create($request->validated());

        return response()->json([
            'message' => 'Parent business and administrator created.',
            'parent' => $this->parents->present($parent),
        ], 201);
    }
}
