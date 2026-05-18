<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InertiaLoginController extends Controller
{
    // Show login page (Inertia React)
    public function create(Request $request)
    {
        return Inertia::render('Auth/Login');

        // return inertia_location(route('login'));
    }

    // Handle login form submission
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
            // return redirect()->route('dashboard');
        }

        $user = auth()->id();

        (new VirtualAccountService())->generate_accounts($user);


        // dd(auth()->user());


        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ])->onlyInput('email');
    }
}
