<?php

/**
 * ROUTE UPDATES FOR AUTH & PROFILE CONSOLIDATION
 * Add these routes to routes/web.php to use the new unified auth pages
 * 
 * These replacements maintain backward compatibility while using the new modern designs
 */

use App\Http\Controllers\ProfileController;

Route::middleware('set_locale','set_affiliate')->group(function () {

    // ============ UNIFIED AUTHENTICATION ROUTES ============
    
    // Login - keep existing route but ensure it points to modern view
    Route::get('/login', fn () => view('auth.login'))->name('login');
    Route::post('/login', [InertiaLoginController::class, 'store'])->name('inertia.login.store');

    // Register - keep existing route
    Route::get('/register', fn () => view('auth.register'))->name('register');

    // UPDATED: Forgot Password - use new modern design
    Route::get('/forgot-password', fn () => view('auth.forgot-password-new'))->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    // UPDATED: Reset Password - use new modern design  
    Route::get('/reset-password/{token}', function($token) {
        return view('auth.reset-password-new', ['request' => request()]);
    })->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');

    // Other auth routes
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');
    Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store'])->middleware(['auth', 'throttle:6,1']);

    // ============ UNIFIED PROFILE ROUTES ============

    Route::middleware('auth')->group(function () {
        
        // UPDATED: Modern profile page with all tabs (View, Password, PIN, Delete)
        // Option 1: Replace existing profile edit
        Route::get('/profile', fn () => view('profile.edit-modern', [
            'user' => auth()->user(),
        ]))->name('profile.edit');
        
        // Profile update endpoints remain the same
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        
        // Password update
        Route::put('/password', [PasswordController::class, 'update'])->name('password.update');
        
        // NEW: Transaction PIN update endpoint
        // Add this to your ProfileController
        Route::post('/profile/update-pin', [ProfileController::class, 'updatePin'])->name('profile.update-pin');
    });

    // ============ REMOVE THESE OLD ROUTES ============
    // Delete or comment out the following deprecated routes:
    
    // OLD: Route::get('template2/login', [Template2Controller::class, 'login'])->name('template2.login');
    // OLD: Route::get('template2/signup', [Template2Controller::class, 'signup'])->name('template2.signup');
    // OLD: Route::get('template2/forgot-password', [Template2Controller::class, 'forgot_password'])->name('template2.forgot_password');
    
    // OLD: Route::get('landing/loginn', fn () => view('landing.loginn'));
    // OLD: Route::get('landing/reg', fn () => view('landing.reg'));
    // OLD: Route::get('landing/forgotpass', fn () => view('landing.forgotpass'));
    // OLD: Route::get('landing/resetpass', fn () => view('landing.resetpass'));

});
