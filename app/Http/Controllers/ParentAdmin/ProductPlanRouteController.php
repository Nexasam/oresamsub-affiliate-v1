<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentAdmin\SwitchProductPlanRouteRequest;
use App\Models\ParentProviderConnection;
use App\Models\ProductPlan;
use App\Services\ParentAdmin\ProductPlanRouteSwitchService;
use Illuminate\Http\RedirectResponse;

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
}

