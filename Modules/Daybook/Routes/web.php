<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Module Installation Routes
|--------------------------------------------------------------------------
| These routes use minimal middleware to avoid accessing daybook tables
| before they are created during installation.
*/
Route::middleware(['web', 'auth'])->group(function () {
    // Installation routes - minimal middleware to avoid table access issues
    Route::get('/daybook/install', [\Modules\Daybook\Http\Controllers\InstallController::class, 'index'])->name('daybook.install');
    Route::get('/daybook/install/update', [\Modules\Daybook\Http\Controllers\InstallController::class, 'update'])->name('daybook.install.update');
    Route::get('/daybook/install/uninstall', [\Modules\Daybook\Http\Controllers\InstallController::class, 'uninstall'])->name('daybook.install.uninstall');
});

/*
|--------------------------------------------------------------------------
| Main Daybook Routes
|--------------------------------------------------------------------------
| These routes are only loaded if the Daybook module is installed.
*/
Route::prefix('daybook')->middleware(['auth', 'web', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'])->group(function() {
    Route::get('/', 'DaybookController@index')->name('daybook.index');
    Route::get('/get-data', 'DaybookController@getData')->name('daybook.getData');
    Route::get('/export', 'DaybookController@export')->name('daybook.export');
    Route::get('/voucher-details', 'DaybookController@getVoucherDetails')->name('daybook.voucherDetails');
    Route::get('/monthly-cashbook', 'DaybookController@monthlyCashbook')->name('daybook.monthlyCashbook');
    Route::get('/monthly-cashbook/get-data', 'DaybookController@getMonthlyCashbookData')->name('daybook.monthlyCashbookData');
    Route::get('/daily-cashbook', 'DaybookController@dailyCashbook')->name('daybook.dailyCashbook');
    Route::get('/daily-cashbook/get-data', 'DaybookController@getDailyCashbookData')->name('daybook.dailyCashbookData');
    Route::get('/monthly-dashboard', 'DaybookController@monthlyDashboard')->name('daybook.monthlyDashboard');
    Route::get('/monthly-dashboard/get-data', 'DaybookController@getMonthlyDashboardData')->name('daybook.monthlyDashboardData');
    Route::get('/daily-payment', 'DaybookController@dailyPayment')->name('daybook.dailyPayment');
    Route::get('/daily-payment/get-data', 'DaybookController@getDailyPaymentData')->name('daybook.dailyPaymentData');
});
