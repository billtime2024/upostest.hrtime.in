<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes - Business Intelligence Module
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Business Intelligence module.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

Route::middleware('auth:api')->prefix('business-intelligence')->group(function () {
    
    // Dashboard API
    Route::get('/dashboard/kpis', 'DashboardController@getKPIs');
    Route::get('/dashboard/charts/{type}', 'DashboardController@getChartData');
    Route::get('/dashboard/performance', 'DashboardController@getPerformanceSummary');

    // Analytics API
    Route::get('/analytics/sales', 'AnalyticsController@getSalesAnalytics');
    Route::get('/analytics/inventory', 'AnalyticsController@getInventoryAnalytics');
    Route::get('/analytics/financial', 'AnalyticsController@getFinancialAnalytics');
    Route::get('/analytics/customer', 'AnalyticsController@getCustomerAnalytics');
    Route::get('/analytics/supplier', 'AnalyticsController@getSupplierAnalytics');

    // Insights API
    Route::get('/insights', 'InsightsController@getInsights');
    Route::post('/insights/generate', 'InsightsController@generateInsights');
    Route::get('/insights/critical', 'InsightsController@getCritical');
    Route::get('/insights/{id}', 'InsightsController@show');
    Route::post('/insights/{id}/acknowledge', 'InsightsController@acknowledge');

    // Configuration API
    Route::get('/configuration', 'ConfigurationController@getConfigurations');
    Route::get('/configuration/{key}', 'ConfigurationController@getConfiguration');
    Route::post('/configuration/update', 'ConfigurationController@updateConfiguration');
});

