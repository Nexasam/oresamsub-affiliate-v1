<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentAdmin\UpdateAffiliateProfitCapsRequest;
use App\Models\Affiliate;
use App\Services\ParentAdmin\AffiliateProfitCapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AffiliateProfitCapController extends Controller
{
    public function __construct(private readonly AffiliateProfitCapService $caps) {}

    public function index(Request $request): JsonResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;

        return response()->json(['affiliates' => Affiliate::where('parent_business_id', $parent->id)->orderBy('name')->get(['id', 'name', 'slug'])]);
    }

    public function show(Request $request, Affiliate $affiliate): JsonResponse
    {
        $this->authorizeAffiliate($request, $affiliate);

        return response()->json(['affiliate' => $affiliate->only(['id', 'name', 'slug']), 'caps' => $this->caps->ensureCaps($affiliate)]);
    }

    public function update(UpdateAffiliateProfitCapsRequest $request, Affiliate $affiliate): JsonResponse
    {
        $this->authorizeAffiliate($request, $affiliate);
        $result = $this->caps->replaceCaps($affiliate, $request->validated('caps'));

        if ($result['violations']->isNotEmpty()) {
            return response()->json(['message' => 'Some existing affiliate prices exceed the proposed maximums.', 'violations' => $result['violations']], 422);
        }

        return response()->json(['message' => 'Affiliate maximum pricing updated.', 'caps' => $result['caps']]);
    }

    private function authorizeAffiliate(Request $request, Affiliate $affiliate): void
    {
        abort_unless($affiliate->parent_business_id === $request->user('parent_admin')->parent_business_id, 404);
    }
}
