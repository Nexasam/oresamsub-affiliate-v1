<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use Illuminate\View\View;

class AffiliateCatalogController extends Controller
{
    public function index(): View
    {
        return view('platform-admin.affiliate-catalog.index', [
            'affiliates' => Affiliate::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
