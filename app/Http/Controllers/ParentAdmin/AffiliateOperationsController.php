<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateOperationsController extends Controller
{
    public function index(Request $request): View
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        $affiliates = $parent->affiliates()->orderBy('name')->get(['id', 'name', 'slug', 'contact_email']);
        $selected = $request->filled('affiliate_id')
            ? $parent->affiliates()->findOrFail($request->integer('affiliate_id'))
            : $affiliates->first();

        return view('parent-admin.operations.index', compact('affiliates', 'selected'));
    }
}
