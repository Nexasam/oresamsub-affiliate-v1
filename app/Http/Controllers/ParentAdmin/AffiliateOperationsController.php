<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $accounts = $selected
            ? User::withoutGlobalScope('affiliate')
                ->where('affiliate_id', $selected->id)
                ->with('role:id,role_name')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->paginate(25, ['*'], 'accounts_page')
                ->withQueryString()
            : null;

        return view('parent-admin.operations.index', compact('affiliates', 'selected', 'accounts'));
    }
}
