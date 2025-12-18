<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => ['web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'], 'prefix' => 'reports', 'namespace' => '\Modules\CustomerMonthlySales\Http\Controllers'], function () {
	// Customer Monthly Sales Report Routes
	Route::get('/customer-monthly-sales', 'CustomerMonthlySalesController@index')
		->name('reports.customer-monthly-sales');

	Route::get('/customer-monthly-sales/data', 'CustomerMonthlySalesController@getCustomerMonthlyData')
		->name('reports.customer-monthly-sales.data');

	Route::get('/customer-monthly-sales/summary', 'CustomerMonthlySalesController@getSummary')
		->name('reports.customer-monthly-sales.summary');

	Route::get('/customer-monthly-sales/details/{customerId}', 'CustomerMonthlySalesController@getCustomerDetails')
		->name('reports.customer-monthly-sales.details');
});

// Install routes (separate group with minimal middleware - like Exchange module)
// Note: These routes must be accessible before module is installed, so minimal middleware
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('customermonthlysales')->group(function () {
        // Installation routes - minimal middleware to avoid table access issues
        Route::get('/install', 'InstallController@index');
        Route::get('/install/update', 'InstallController@update');
        Route::get('/install/uninstall', 'InstallController@uninstall');
    });
});

