<?php

use App\Http\Controllers\ParentAdmin\AuthController;
use App\Http\Controllers\ParentAdmin\DashboardController;
use App\Http\Controllers\ParentAdmin\ProductPlanController;
use Illuminate\Support\Facades\Route;

Route::prefix('parent-admin')->name('parent-admin.')->group(function () {
    Route::middleware('guest:parent_admin')->group(function () {
        Route::get('login', [AuthController::class, 'create'])->name('login');
        Route::post('login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:parent_admin')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('logout', [AuthController::class, 'destroy'])->name('logout');
        Route::get('product-plans', [ProductPlanController::class, 'index'])->name('product-plans.index');
        Route::get('product-plans/data', [ProductPlanController::class, 'data'])->name('product-plans.data');
        Route::post('product-plans', [ProductPlanController::class, 'store'])->name('product-plans.store');
        Route::patch('product-plans/{plan}', [ProductPlanController::class, 'update'])->name('product-plans.update');
    });
});
