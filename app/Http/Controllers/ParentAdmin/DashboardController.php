<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Services\ParentAdmin\PlanHealthAlertService;
use App\Services\ParentAdmin\PlanHealthNotificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        PlanHealthAlertService $healthAlerts,
        PlanHealthNotificationService $notifications,
    ): View
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        $alerts = $healthAlerts->forParent($parent);
        $notifications->sync($parent, $alerts);

        return view('parent-admin.dashboard', [
            'planHealthAlerts' => $alerts,
            'healthNotificationCount' => $request->user('parent_admin')->unreadNotifications()
                ->where('type', \App\Notifications\ParentPlanHealthNotification::class)->count(),
        ]);
    }
}
