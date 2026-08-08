<?php

use App\Http\Controllers\ParentAdmin\AuthController;
use App\Http\Controllers\ParentAdmin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('parent-admin')->name('parent-admin.')->group(function () {
    Route::middleware('guest:parent_admin')->group(function () {
        Route::get('login', [AuthController::class, 'create'])->name('login');
        Route::post('login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:parent_admin')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('logout', [AuthController::class, 'destroy'])->name('logout');
    });
});
