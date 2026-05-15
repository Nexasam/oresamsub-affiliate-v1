<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AffiliateController extends Controller
{
    // LIST PAGE (table view)
    public function index()
    {
        $affiliates = Affiliate::latest()->get();

        return view('affiliates.index', compact('affiliates'));
    }

    // EDIT PAGE
    public function edit()
    {
        $affiliate = session('affiliate');

        if (!$affiliate) {
            abort(403, 'Unauthorized');
        }
    
        return view('affiliates.edit', compact('affiliate'));
    }

    // UPDATE ACTION
    public function update(Request $request)
    {
        $affiliate = Session::get('affiliate');
        // logger()
    
        if (!$affiliate) {
            logger('sdfsss');
            abort(403, 'Unauthorized');
        }
    
        $affiliate = Affiliate::findOrFail($affiliate->id); // fresh instance
    
        $request->validate([
            'parent_key' => 'required|string|max:255',
            'parent_email' => 'required|email|max:255',
            // 'contact_email' => 'required|email',
            // 'contact_phone' => 'required|string',
            // 'domain_url' => 'nullable|string',
            // 'activation_status' => 'required|in:0,1',
        ]);
    
        $affiliate->update($request->only([
            'parent_key',
            'parent_email',
            // 'contact_email',
            // 'contact_phone',
            // 'domain_url',
            // 'activation_status',
        ]));
    
        // 🔥 refresh session
        Session::put('affiliate', $affiliate->fresh());
    
        return redirect()
            ->back()
            ->with('success', 'Affiliate updated successfully');
    }

    // DELETE
    public function destroy($id)
    {
        $affiliate = Affiliate::findOrFail($id);
        $affiliate->delete();

        return redirect()
            ->route('affiliates.index')
            ->with('success', 'Affiliate deleted successfully');
    }
}