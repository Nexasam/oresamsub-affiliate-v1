<?php

use App\Http\Controllers\PlatformAdmin\AffiliateCatalogController;
use App\Http\Controllers\PlatformAdmin\AffiliateController;
use App\Http\Controllers\PlatformAdmin\AffiliateOnboardingController;
use App\Http\Controllers\PlatformAdmin\AffiliateOperationsController;
use App\Http\Controllers\PlatformAdmin\AffiliateUsersController;
use App\Http\Controllers\PlatformAdmin\AuthController;
use App\Http\Controllers\PlatformAdmin\CatalogController;
use App\Http\Controllers\PlatformAdmin\DashboardController;
use App\Http\Controllers\PlatformAdmin\FundingProviderController;
use App\Http\Controllers\PlatformAdmin\ImpersonationController;
use App\Http\Controllers\PlatformAdmin\ParentBusinessController;
use App\Http\Controllers\PlatformAdmin\ParentProviderConnectionController;
use App\Http\Controllers\PlatformAdmin\ProviderAdapterController;
use App\Http\Controllers\PlatformAdmin\ReportController;
use App\Http\Controllers\PlatformAdmin\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('platform-admin.')->group(function () {
    Route::middleware('guest:platform_admin')->group(function () {
        Route::get('login', [AuthController::class, 'create'])->name('login');
        Route::post('login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:platform_admin')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('logout', [AuthController::class, 'destroy'])->name('logout');
        Route::get('parent-businesses', [ParentBusinessController::class, 'index'])->name('parent-businesses.index');
        Route::get('parent-businesses/data', [ParentBusinessController::class, 'data'])->name('parent-businesses.data');
        Route::post('parent-businesses', [ParentBusinessController::class, 'store'])->name('parent-businesses.store');
        Route::get('all-transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('all-transactions/data', [TransactionController::class, 'data'])->name('transactions.data');
        Route::patch('all-transactions/{transaction}/status', [TransactionController::class, 'updateStatus'])->name('transactions.status.update');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/data', [ReportController::class, 'data'])->name('reports.data');
        Route::get('provider-adapters', [ProviderAdapterController::class, 'index'])->name('provider-adapters.index');
        Route::get('provider-adapters/data', [ProviderAdapterController::class, 'data'])->name('provider-adapters.data');
        Route::post('provider-adapters', [ProviderAdapterController::class, 'store'])->name('provider-adapters.store');
        Route::put('provider-adapters/{providerAdapter}', [ProviderAdapterController::class, 'update'])->name('provider-adapters.update');
        Route::get('funding-providers', [FundingProviderController::class, 'index'])->name('funding-providers.index');
        Route::post('funding-providers', [FundingProviderController::class, 'store'])->name('funding-providers.store');
        Route::put('funding-providers/{fundingProvider}', [FundingProviderController::class, 'update'])->name('funding-providers.update');
        Route::get('provider-connections', [ParentProviderConnectionController::class, 'index'])->name('provider-connections.index');
        Route::get('provider-connections/data', [ParentProviderConnectionController::class, 'data'])->name('provider-connections.data');
        Route::patch('provider-connections/{connection}/review', [ParentProviderConnectionController::class, 'review'])->name('provider-connections.review');
        Route::get('catalog', [CatalogController::class, 'index'])->name('catalog.index');
        Route::get('catalog/data', [CatalogController::class, 'data'])->name('catalog.data');
        Route::patch('catalog/categories/{category}', [CatalogController::class, 'updateCategory'])->name('catalog.categories.update');
        Route::patch('catalog/plans/{plan}', [CatalogController::class, 'updatePlan'])->name('catalog.plans.update');
        Route::get('affiliate-catalog', [AffiliateCatalogController::class, 'index'])->name('affiliate-catalog.index');
        Route::get('affiliate-users', [AffiliateUsersController::class, 'index'])->name('affiliate-users.index');
        Route::get('operations', [AffiliateOperationsController::class, 'standalone'])->name('operations.index');
        Route::get('affiliate-users/data', [AffiliateUsersController::class, 'allData'])->name('affiliate-users.all-data');
        Route::get('affiliates/{affiliate}/management-users', [AffiliateUsersController::class, 'data'])->name('affiliate-users.data');
        Route::patch('affiliates/{affiliate}/management-users/{user}', [AffiliateUsersController::class, 'updateUser'])->name('affiliate-users.update');
        Route::patch('affiliates/{affiliate}/management-user-plans/{plan}', [AffiliateUsersController::class, 'updatePlan'])->name('affiliate-users.plans.update');
        Route::post('affiliates/{affiliate}/management-users/{user}/impersonate', [ImpersonationController::class, 'create'])->name('affiliate-users.impersonate');
        Route::post('affiliates/{affiliate}/management-user-plans/generate', [AffiliateUsersController::class, 'generatePlans'])->name('affiliate-users.plans.generate');

        Route::get('affiliates', [AffiliateController::class, 'index'])->name('affiliates.index');
        Route::get('affiliate-onboarding', [AffiliateOnboardingController::class, 'index'])->name('affiliate-onboarding.index');
        Route::patch('affiliate-onboarding/{onboarding}/review', [AffiliateOnboardingController::class, 'review'])->name('affiliate-onboarding.review');
        Route::get('affiliates/{affiliate}', [AffiliateController::class, 'show'])->name('affiliates.show');
        Route::patch('affiliates/{affiliate}', [AffiliateController::class, 'update'])->name('affiliates.update');
        Route::get('affiliates/{affiliate}/users', [AffiliateController::class, 'users'])->name('affiliates.users');
        Route::post('affiliates/{affiliate}/users', [AffiliateController::class, 'storeUser'])->name('affiliates.users.store');
        Route::patch('affiliates/{affiliate}/users/{user}', [AffiliateController::class, 'updateUser'])->name('affiliates.users.update');
        Route::get('affiliates/{affiliate}/users/{user}', [AffiliateController::class, 'showUser'])->name('affiliates.users.show');
        Route::post('affiliates/{affiliate}/users/{user}/credit', [AffiliateController::class, 'creditUser'])->name('affiliates.users.credit');
        Route::get('affiliates/{affiliate}/transactions', [AffiliateController::class, 'transactions'])->name('affiliates.transactions');
        Route::get('affiliates/{affiliate}/bank-codes', [AffiliateController::class, 'bankCodes'])->name('affiliates.bank-codes');
        Route::patch('affiliates/{affiliate}/bank-codes/{bankCode}', [AffiliateController::class, 'updateBankCode'])->name('affiliates.bank-codes.update');
        Route::post('affiliates/{affiliate}/bank-codes', [AffiliateController::class, 'storeBankCode'])->name('affiliates.bank-codes.store');
        Route::get('affiliates/{affiliate}/funding-options', [AffiliateController::class, 'fundingOptions'])->name('affiliates.funding-options');
        Route::patch('affiliates/{affiliate}/funding-options/{fundingOption}', [AffiliateController::class, 'updateFundingOption'])->name('affiliates.funding-options.update');
        Route::get('affiliates/{affiliate}/operations', [AffiliateOperationsController::class, 'index'])->name('affiliates.operations');
        Route::get('affiliates/{affiliate}/catalog', [AffiliateOperationsController::class, 'catalog'])->name('affiliates.catalog');
        Route::patch('affiliates/{affiliate}/catalog/plans/{plan}', [AffiliateOperationsController::class, 'updatePlan'])->name('affiliates.catalog.plans.update');
        Route::patch('affiliates/{affiliate}/catalog/categories/{category}', [AffiliateOperationsController::class, 'updateCategory'])->name('affiliates.catalog.categories.update');
        Route::get('affiliates/{affiliate}/wallet-logs', [AffiliateOperationsController::class, 'walletLogs'])->name('affiliates.wallet-logs');
        Route::patch('affiliates/{affiliate}/margin-defaults', [AffiliateOperationsController::class, 'updateMargins'])->name('affiliates.margin-defaults.update');
        Route::post('affiliates/{affiliate}/catalog/categories/generate', [AffiliateOperationsController::class, 'generateCategories'])->name('affiliates.catalog.categories.generate');
        Route::post('affiliates/{affiliate}/catalog/plans/generate', [AffiliateOperationsController::class, 'generatePlans'])->name('affiliates.catalog.plans.generate');
    });
});
