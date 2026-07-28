<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('platform-admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['active'] = true;

        if (! Auth::guard('platform_admin')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match an active platform administrator.',
            ]);
        }

        $request->session()->regenerate();
        Auth::guard('platform_admin')->user()->forceFill(['last_login_at' => now()])->save();

        // Never reuse an affiliate-side intended URL for this separate guard.
        $request->session()->forget('url.intended');

        return redirect()->route('platform-admin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('platform_admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('platform-admin.login');
    }
}
