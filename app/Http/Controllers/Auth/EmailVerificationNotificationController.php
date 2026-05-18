<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    // public function store(Request $request): RedirectResponse
    // {
    //     if ($request->user()->hasVerifiedEmail()) {
    //         return redirect()->intended(route('dashboard', absolute: false));
    //     }

    //     $request->user()->sendEmailVerificationNotification();

    //     return back()->with('status', 'verification-link-sent');
    // }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back()->with('success', 'Already verified');
        }

        // if ($request->user()->hasVerifiedEmail()) {
        //     return redirect()
        //         ->route('dashboard')
        //         ->with('success', 'Your email is already verified.');
        // }
    
        $request->user()->sendEmailVerificationNotification();
    
        return back()->with('success', 'Verification link sent successfully');
    }
}
