<?php

/*
|--------------------------------------------------------------------------
| Web Routes - Business Intelligence Module
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for the Business Intelligence module.
|
*/

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'])->prefix('business-intelligence')->group(function () {

    // Dashboard Routes
    Route::get('/dashboard', [Modules\BusinessIntelligence\Http\Controllers\DashboardController::class, 'index'])->name('businessintelligence.dashboard');
    Route::get('/dashboard/kpis', [Modules\BusinessIntelligence\Http\Controllers\DashboardController::class, 'getKPIs'])->name('businessintelligence.dashboard.kpis');
    Route::get('/dashboard/chart-data', [Modules\BusinessIntelligence\Http\Controllers\DashboardController::class, 'getChartData'])->name('businessintelligence.dashboard.chart-data');
    Route::get('/dashboard/performance', [Modules\BusinessIntelligence\Http\Controllers\DashboardController::class, 'getPerformanceSummary'])->name('businessintelligence.dashboard.performance');
    Route::post('/dashboard/refresh', [Modules\BusinessIntelligence\Http\Controllers\DashboardController::class, 'refreshData'])->name('businessintelligence.dashboard.refresh');
    Route::get('/dashboard/export', [Modules\BusinessIntelligence\Http\Controllers\DashboardController::class, 'exportDashboard'])->name('businessintelligence.dashboard.export');

    // Advanced KPI Routes
    Route::get('/advanced-kpi', [Modules\BusinessIntelligence\Http\Controllers\DashboardController::class, 'advancedKpi'])->name('businessintelligence.advanced-kpi');
    Route::get('/advanced-kpi/data', [Modules\BusinessIntelligence\Http\Controllers\DashboardController::class, 'getAdvancedKpiData'])->name('businessintelligence.advanced-kpi.data');

    // Analytics Routes
    Route::prefix('analytics')->group(function () {
        Route::get('/sales', [Modules\BusinessIntelligence\Http\Controllers\AnalyticsController::class, 'getSalesAnalytics'])->name('businessintelligence.analytics.sales');
        Route::get('/sales/data', [Modules\BusinessIntelligence\Http\Controllers\AnalyticsController::class, 'getSalesAnalyticsData'])->name('businessintelligence.analytics.sales.data');
        Route::get('/inventory', [Modules\BusinessIntelligence\Http\Controllers\AnalyticsController::class, 'getInventoryAnalytics'])->name('businessintelligence.analytics.inventory');
        Route::get('/financial', [Modules\BusinessIntelligence\Http\Controllers\AnalyticsController::class, 'getFinancialAnalytics'])->name('businessintelligence.analytics.financial');
        Route::get('/customer', [Modules\BusinessIntelligence\Http\Controllers\AnalyticsController::class, 'getCustomerAnalytics'])->name('businessintelligence.analytics.customer');
        Route::get('/supplier', [Modules\BusinessIntelligence\Http\Controllers\AnalyticsController::class, 'getSupplierAnalytics'])->name('businessintelligence.analytics.supplier');
        Route::get('/comprehensive', [Modules\BusinessIntelligence\Http\Controllers\AnalyticsController::class, 'getComprehensiveAnalytics'])->name('businessintelligence.analytics.comprehensive');
        Route::match(['get', 'post'], '/export', [Modules\BusinessIntelligence\Http\Controllers\AnalyticsController::class, 'exportAnalytics'])->name('businessintelligence.analytics.export');
    });

    // Insights Routes
    Route::prefix('insights')->group(function () {
        Route::get('/', [Modules\BusinessIntelligence\Http\Controllers\InsightsController::class, 'index'])->name('businessintelligence.insights.index');
        Route::get('/data', [Modules\BusinessIntelligence\Http\Controllers\InsightsController::class, 'getInsights'])->name('businessintelligence.insights.data');
        Route::post('/generate', [Modules\BusinessIntelligence\Http\Controllers\InsightsController::class, 'generateInsights'])->name('businessintelligence.insights.generate');
        Route::get('/type/{type}', [Modules\BusinessIntelligence\Http\Controllers\InsightsController::class, 'getByType'])->name('businessintelligence.insights.by-type');
        Route::get('/critical', [Modules\BusinessIntelligence\Http\Controllers\InsightsController::class, 'getCritical'])->name('businessintelligence.insights.critical');
        Route::get('/{id}', [Modules\BusinessIntelligence\Http\Controllers\InsightsController::class, 'show'])->name('businessintelligence.insights.show');
        Route::post('/{id}/acknowledge', [Modules\BusinessIntelligence\Http\Controllers\InsightsController::class, 'acknowledge'])->name('businessintelligence.insights.acknowledge');
        Route::post('/{id}/dismiss', [Modules\BusinessIntelligence\Http\Controllers\InsightsController::class, 'dismiss'])->name('businessintelligence.insights.dismiss');
        Route::post('/{id}/resolve', [Modules\BusinessIntelligence\Http\Controllers\InsightsController::class, 'resolve'])->name('businessintelligence.insights.resolve');
    });

    // Configuration Routes
    Route::prefix('configuration')->group(function () {
        Route::get('/', [Modules\BusinessIntelligence\Http\Controllers\ConfigurationController::class, 'index'])->name('businessintelligence.configuration.index');
        Route::get('/data', [Modules\BusinessIntelligence\Http\Controllers\ConfigurationController::class, 'getConfigurations'])->name('businessintelligence.configuration.data');
        Route::get('/{key}', [Modules\BusinessIntelligence\Http\Controllers\ConfigurationController::class, 'getConfiguration'])->name('businessintelligence.configuration.show');
        Route::post('/update', [Modules\BusinessIntelligence\Http\Controllers\ConfigurationController::class, 'updateConfiguration'])->name('businessintelligence.configuration.update');
        Route::post('/update-multiple', [Modules\BusinessIntelligence\Http\Controllers\ConfigurationController::class, 'updateMultiple'])->name('businessintelligence.configuration.update-multiple');
        Route::delete('/{key}', [Modules\BusinessIntelligence\Http\Controllers\ConfigurationController::class, 'deleteConfiguration'])->name('businessintelligence.configuration.delete');
        Route::post('/reset-defaults', [Modules\BusinessIntelligence\Http\Controllers\ConfigurationController::class, 'resetToDefaults'])->name('businessintelligence.configuration.reset');
    });

    // Installation & Update Routes
    Route::get('/install', [Modules\BusinessIntelligence\Http\Controllers\InstallController::class, 'index'])->name('businessintelligence.install');
    Route::post('/install', [Modules\BusinessIntelligence\Http\Controllers\InstallController::class, 'install'])->name('businessintelligence.install.process');
    Route::post('/install-direct', [Modules\BusinessIntelligence\Http\Controllers\AlternativeInstallController::class, 'installDirect'])->name('businessintelligence.install.direct');
    Route::get('/update', [Modules\BusinessIntelligence\Http\Controllers\InstallController::class, 'update'])->name('businessintelligence.update');
    Route::get('/uninstall', [Modules\BusinessIntelligence\Http\Controllers\InstallController::class, 'uninstall'])->name('businessintelligence.uninstall');
    Route::get('/status', [Modules\BusinessIntelligence\Http\Controllers\InstallController::class, 'status'])->name('businessintelligence.status');
});
