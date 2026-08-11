<?php

use App\Http\Controllers\ParentAdmin\AffiliateProfitCapController;
use App\Http\Controllers\ParentAdmin\AuthController;
use App\Http\Controllers\ParentAdmin\DashboardController;
use App\Http\Controllers\ParentAdmin\PricingController;
use App\Http\Controllers\ParentAdmin\ProductPlanController;
use App\Http\Controllers\ParentAdmin\ProviderConnectionController;
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
        Route::post('product-plans/bulk', [ProductPlanController::class, 'bulkStore'])->name('product-plans.bulk-store');
        Route::patch('product-plans/{plan}', [ProductPlanController::class, 'update'])->name('product-plans.update');
        Route::get('provider-connections', [ProviderConnectionController::class, 'index'])->name('provider-connections.index');
        Route::get('provider-connections/data', [ProviderConnectionController::class, 'data'])->name('provider-connections.data');
        Route::post('provider-connections', [ProviderConnectionController::class, 'store'])->name('provider-connections.store');
        Route::put('provider-connections/{connection}', [ProviderConnectionController::class, 'update'])->name('provider-connections.update');
        Route::get('pricing', [PricingController::class, 'index'])->name('pricing.index');
        Route::get('pricing/data', [PricingController::class, 'data'])->name('pricing.data');
        Route::put('pricing/defaults', [PricingController::class, 'updateDefaults'])->name('pricing.defaults.update');
        Route::put('pricing/levels', [PricingController::class, 'updateLevels'])->name('pricing.levels.update');
        Route::post('pricing/levels/generate-six', [PricingController::class, 'generateSix'])->name('pricing.levels.generate-six');
        Route::put('pricing/plans/{plan}', [PricingController::class, 'updatePrices'])->name('pricing.plans.update');
        Route::delete('pricing/plans/{plan}/levels/{level}', [PricingController::class, 'clearOverride'])->name('pricing.plans.overrides.destroy');
        Route::get('pricing/affiliates', [AffiliateProfitCapController::class, 'index'])->name('pricing.affiliates.index');
        Route::get('pricing/affiliates/{affiliate}/caps', [AffiliateProfitCapController::class, 'show'])->name('pricing.affiliates.caps.show');
        Route::put('pricing/affiliates/{affiliate}/caps', [AffiliateProfitCapController::class, 'update'])->name('pricing.affiliates.caps.update');
    });
});
