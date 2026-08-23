<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerUiPreferenceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $allowed = config('customer-ui.v2_enabled') ? ['v1', 'v2'] : ['v1'];
        $validated = $request->validate([
            'version' => ['required', Rule::in($allowed)],
        ]);

        $request->user()->forceFill([
            'customer_ui_version' => $validated['version'],
        ])->save();

        return back()->with('success', 'Interface preference updated.');
    }
}
