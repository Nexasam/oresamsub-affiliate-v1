<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentAdmin\BulkSwitchProductPlanRoutesRequest;
use App\Http\Requests\ParentAdmin\SwitchProductPlanRouteRequest;
use App\Models\ParentProviderConnection;
use App\Models\ProductPlan;
use App\Services\ParentAdmin\ProductPlanRouteSwitchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductPlanRouteController extends Controller
{
    public function update(
        SwitchProductPlanRouteRequest $request,
        ProductPlan $plan,
        ProductPlanRouteSwitchService $switcher,
    ): RedirectResponse {
        $parent = $request->user('parent_admin')->parentBusiness;
        $connection = ParentProviderConnection::query()
            ->where('parent_business_id', $parent->id)
            ->findOrFail($request->integer('parent_provider_connection_id'));

        $switcher->switch($parent, $plan, $connection, $request->string('provider_plan_id')->toString());

        return redirect()->route('parent-admin.dashboard')->with('success', "Provider route switched to {$connection->name}.");
    }

    public function bulkUpdate(BulkSwitchProductPlanRoutesRequest $request, ProductPlanRouteSwitchService $switcher): RedirectResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        $connection = ParentProviderConnection::query()
            ->where('parent_business_id', $parent->id)
            ->findOrFail($request->integer('parent_provider_connection_id'));
        $submitted = collect($request->validated('plans'));
        $plans = ProductPlan::query()
            ->where('parent_business_id', $parent->id)
            ->whereIn('id', $submitted->pluck('product_plan_id'))
            ->with('providerRoutes')
            ->get()->keyBy('id');

        if ($plans->count() !== $submitted->count()) {
            throw ValidationException::withMessages(['plans' => 'One or more selected plans do not belong to this parent.']);
        }

        DB::transaction(function () use ($submitted, $plans, $connection, $parent, $switcher): void {
            foreach ($submitted->values() as $index => $row) {
                $plan = $plans->get((int) $row['product_plan_id']);
                $providerPlanId = trim((string) ($row['provider_plan_id'] ?? ''));
                if ($providerPlanId === '') {
                    $providerPlanId = (string) $plan->providerRoutes
                        ->firstWhere('parent_provider_connection_id', $connection->id)?->provider_plan_id;
                }
                if ($providerPlanId === '') {
                    throw ValidationException::withMessages([
                        "plans.{$index}.provider_plan_id" => "Enter the provider plan ID for {$plan->product_plan_name}.",
                    ]);
                }
                $switcher->switch($parent, $plan, $connection, $providerPlanId);
            }
        });

        return redirect()->route('parent-admin.product-plans.index')
            ->with('success', $submitted->count().' product plan connections switched.');
    }
}
