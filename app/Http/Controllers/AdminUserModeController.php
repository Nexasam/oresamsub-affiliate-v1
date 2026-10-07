<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminUserModeController extends Controller
{
    public function enter(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->role?->role_name === 'Admin', 403);

        $request->session()->put('admin_user_mode', true);

        return redirect()->route('dashboard')->with('success', 'User mode is now active.');
    }

    public function exit(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_user_mode');

        return redirect()->route('dashboard')->with('success', 'Admin mode restored.');
    }
}
