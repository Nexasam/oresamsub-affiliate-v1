<?php

use App\Http\Controllers\PlatformAdmin\AffiliateController;
use App\Http\Controllers\PlatformAdmin\AuthController;
use App\Http\Controllers\PlatformAdmin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('platform-admin.')->group(function () {
    Route::middleware('guest:platform_admin')->group(function () {
        Route::get('login', [AuthController::class, 'create'])->name('login');
        Route::post('login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:platform_admin')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('logout', [AuthController::class, 'destroy'])->name('logout');

        Route::get('affiliates', [AffiliateController::class, 'index'])->name('affiliates.index');
        Route::get('affiliates/{affiliate}', [AffiliateController::class, 'show'])->name('affiliates.show');
        Route::patch('affiliates/{affiliate}', [AffiliateController::class, 'update'])->name('affiliates.update');
        Route::get('affiliates/{affiliate}/users', [AffiliateController::class, 'users'])->name('affiliates.users');
        Route::post('affiliates/{affiliate}/users', [AffiliateController::class, 'storeUser'])->name('affiliates.users.store');
        Route::patch('affiliates/{affiliate}/users/{user}', [AffiliateController::class, 'updateUser'])->name('affiliates.users.update');
        Route::get('affiliates/{affiliate}/transactions', [AffiliateController::class, 'transactions'])->name('affiliates.transactions');
        Route::get('affiliates/{affiliate}/bank-codes', [AffiliateController::class, 'bankCodes'])->name('affiliates.bank-codes');
        Route::patch('affiliates/{affiliate}/bank-codes/{bankCode}', [AffiliateController::class, 'updateBankCode'])->name('affiliates.bank-codes.update');
    });
});
