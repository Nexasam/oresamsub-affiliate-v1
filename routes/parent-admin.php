<?php

use App\Http\Controllers\ParentAdmin\AffiliateController;
use App\Http\Controllers\ParentAdmin\AffiliateOperationsController;
use App\Http\Controllers\ParentAdmin\AffiliateProfitCapController;
use App\Http\Controllers\ParentAdmin\AffiliateProcessingController;
use App\Http\Controllers\ParentAdmin\AffiliateSettlementWalletController;
use App\Http\Controllers\ParentAdmin\AuthController;
use App\Http\Controllers\ParentAdmin\DashboardController;
use App\Http\Controllers\ParentAdmin\FundingProviderController;
use App\Http\Controllers\ParentAdmin\PricingController;
use App\Http\Controllers\ParentAdmin\ProductPlanController;
use App\Http\Controllers\ParentAdmin\ProviderConnectionController;
use App\Http\Controllers\ParentAdmin\TransactionController;
use App\Http\Controllers\PlatformAdmin\AffiliateController as PlatformAffiliateController;
use App\Http\Controllers\PlatformAdmin\AffiliateOperationsController as PlatformAffiliateOperationsController;
use App\Http\Controllers\PlatformAdmin\AffiliateUsersController as PlatformAffiliateUsersController;
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
        Route::get('product-plans/{plan}/edit', [ProductPlanController::class, 'edit'])->name('product-plans.edit');
        Route::put('product-plans/{plan}/configuration', [ProductPlanController::class, 'updateConfiguration'])->name('product-plans.configuration.update');
        Route::patch('product-plans/{plan}', [ProductPlanController::class, 'update'])->name('product-plans.update');
        Route::get('affiliates', [AffiliateController::class, 'index'])->name('affiliates.index');
        Route::get('settlement-wallets', [AffiliateSettlementWalletController::class, 'index'])->name('settlement-wallets.index');
        Route::get('operations', [AffiliateOperationsController::class, 'index'])->name('operations.index');
        Route::post('affiliates', [AffiliateController::class, 'store'])->name('affiliates.store');
        Route::get('affiliates/{affiliate}/edit', [AffiliateController::class, 'edit'])->name('affiliates.edit');
        Route::put('affiliates/{affiliate}', [AffiliateController::class, 'update'])->name('affiliates.update');
        Route::post('affiliates/{affiliate}/attach', [AffiliateController::class, 'attach'])->name('affiliates.attach');
        Route::patch('affiliates/{affiliate}/level', [AffiliateController::class, 'updateLevel'])->name('affiliates.level.update');
        Route::post('affiliates/{affiliate}/categories/sync', [AffiliateController::class, 'syncCategories'])->name('affiliates.categories.sync');
        Route::middleware('parent.affiliate')->group(function () {
            Route::get('affiliates/{affiliate}/settlement-wallet', [AffiliateSettlementWalletController::class, 'show'])->name('affiliates.settlement-wallet.show');
            Route::post('affiliates/{affiliate}/settlement-wallet/credits', [AffiliateSettlementWalletController::class, 'credit'])->name('affiliates.settlement-wallet.credit');
            Route::post('affiliates/{affiliate}/processing/change-requests', [AffiliateProcessingController::class, 'requestChange'])->name('affiliates.processing.change-requests.store');
            Route::get('affiliates/{affiliate}/catalog', [PlatformAffiliateOperationsController::class, 'catalog'])->name('affiliates.catalog');
            Route::patch('affiliates/{affiliate}/catalog/plans/{plan}', [PlatformAffiliateOperationsController::class, 'updatePlan'])->name('affiliates.catalog.plans.update');
            Route::patch('affiliates/{affiliate}/catalog/categories/{category}', [PlatformAffiliateOperationsController::class, 'updateCategory'])->name('affiliates.catalog.categories.update');
            Route::get('affiliates/{affiliate}/wallet-logs', [PlatformAffiliateOperationsController::class, 'walletLogs'])->name('affiliates.wallet-logs');
            Route::patch('affiliates/{affiliate}/margin-defaults', [PlatformAffiliateOperationsController::class, 'updateMargins'])->name('affiliates.margin-defaults.update');
            Route::post('affiliates/{affiliate}/catalog/categories/generate', [PlatformAffiliateOperationsController::class, 'generateCategories'])->name('affiliates.catalog.categories.generate');
            Route::post('affiliates/{affiliate}/catalog/plans/generate', [PlatformAffiliateOperationsController::class, 'generatePlans'])->name('affiliates.catalog.plans.generate');
            Route::get('affiliates/{affiliate}/management-users', [PlatformAffiliateUsersController::class, 'data'])->name('affiliate-users.data');
            Route::patch('affiliates/{affiliate}/management-users/{user}', [PlatformAffiliateUsersController::class, 'updateUser'])->name('affiliate-users.update');
            Route::patch('affiliates/{affiliate}/management-user-plans/{plan}', [PlatformAffiliateUsersController::class, 'updatePlan'])->name('affiliate-users.plans.update');
            Route::post('affiliates/{affiliate}/management-user-plans/generate', [PlatformAffiliateUsersController::class, 'generatePlans'])->name('affiliate-users.plans.generate');
            Route::get('affiliates/{affiliate}/transactions', [PlatformAffiliateController::class, 'transactions'])->name('affiliates.transactions');
        });
        Route::get('provider-connections', [ProviderConnectionController::class, 'index'])->name('provider-connections.index');
        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('provider-connections/data', [ProviderConnectionController::class, 'data'])->name('provider-connections.data');
        Route::post('provider-connections', [ProviderConnectionController::class, 'store'])->name('provider-connections.store');
        Route::put('provider-connections/{connection}', [ProviderConnectionController::class, 'update'])->name('provider-connections.update');
        Route::get('funding-providers', [FundingProviderController::class, 'index'])->name('funding-providers.index');
        Route::post('funding-providers/{fundingProvider}/enable', [FundingProviderController::class, 'enable'])->name('funding-providers.enable');
        Route::get('funding-providers/{parentProvider}/banks', [FundingProviderController::class, 'banks'])->name('funding-providers.banks.index');
        Route::post('funding-providers/{parentProvider}/banks', [FundingProviderController::class, 'storeBank'])->name('funding-providers.banks.store');
        Route::put('funding-providers/{parentProvider}/banks/{bank}', [FundingProviderController::class, 'updateBank'])->name('funding-providers.banks.update');
        Route::get('funding-providers/{parentProvider}/affiliates', [FundingProviderController::class, 'affiliates'])->name('funding-providers.affiliates.index');
        Route::put('funding-providers/{parentProvider}/affiliates/{affiliate}', [FundingProviderController::class, 'configureAffiliate'])->name('funding-providers.affiliates.update');
        Route::patch('funding-mode-requests/{modeRequest}', [FundingProviderController::class, 'reviewMode'])->name('funding-mode-requests.review');
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
