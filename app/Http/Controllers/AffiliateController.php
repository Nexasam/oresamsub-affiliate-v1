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
    public function updatolde(Request $request)
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

    public function updateold(Request $request)
{
    $affiliate = Session::get('affiliate');

    if (!$affiliate) {
        abort(403, 'Unauthorized');
    }

    $affiliate = Affiliate::findOrFail($affiliate->id);

    $request->validate([
        'parent_key' => 'required|string|max:255',
        'parent_email' => 'required|email|max:255',
        'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    // update text fields
    $affiliate->parent_key = $request->parent_key;
    $affiliate->parent_email = $request->parent_email;

    // HANDLE LOGO UPLOAD
    if ($request->hasFile('logo')) {

        // delete old logo if exists
        if ($affiliate->logo && file_exists(public_path($affiliate->logo))) {
            unlink(public_path($affiliate->logo));
        }

        $file = $request->file('logo');

        $filename = time() . '_' . $file->getClientOriginalName();

        $path = $file->move(public_path('uploads/affiliates'), $filename);

        $affiliate->logo = 'uploads/affiliates/' . $filename;
    }

    $affiliate->save();

    Session::put('affiliate', $affiliate->fresh());

    return redirect()
        ->back()
        ->with('success', 'Affiliate updated successfully');
    }

      // UPDATE ACTION
      public function update(Request $request)
      {
          $affiliate = Session::get('affiliate');
  
          if (!$affiliate) {
              abort(403, 'Unauthorized');
          }
  
          $affiliate = Affiliate::findOrFail($affiliate->id);
  
          $request->validate([
              'parent_key' => 'required|string|max:255',
              'parent_email' => 'required|email|max:255',
              'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
          ]);
  
          // Update fields
          $affiliate->parent_key = $request->parent_key;
          $affiliate->parent_email = $request->parent_email;
  
          // HANDLE LOGO UPLOAD
          if ($request->hasFile('logo')) {
  
              // delete old logo if exists
              if (
                  $affiliate->logo &&
                  file_exists(public_path($affiliate->logo))
              ) {
                  @unlink(public_path($affiliate->logo));
              }
  
              $file = $request->file('logo');
  
              $filename = time() . '_' . $file->getClientOriginalName();
  
              // ensure folder exists
              $destinationPath = public_path('uploads/affiliates');
  
              if (!file_exists($destinationPath)) {
                  mkdir($destinationPath, 0755, true);
              }
  
              // move file
              $file->move($destinationPath, $filename);
  
              // save path
              $affiliate->logo = 'uploads/affiliates/' . $filename;
          }
  
          $affiliate->save();
  
          // refresh session affiliate
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