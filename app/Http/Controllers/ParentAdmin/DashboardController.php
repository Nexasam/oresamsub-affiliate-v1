<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Services\ParentAdmin\PlanHealthAlertService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PlanHealthAlertService $healthAlerts): View
    {
        $parent = $request->user('parent_admin')->parentBusiness;

        return view('parent-admin.dashboard', [
            'planHealthAlerts' => $healthAlerts->forParent($parent),
        ]);
    }
}
