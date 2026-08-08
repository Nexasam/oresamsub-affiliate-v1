<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('parent-admin.dashboard');
    }
}
