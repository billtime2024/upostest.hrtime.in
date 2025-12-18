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

Route::middleware('web', 'SetSessionData', 'auth', 'language', 'timezone', 'AdminSidebarMenu')->prefix('schemes')->group(function () {
    Route::get('/', [\Modules\Scheme\Http\Controllers\SchemeController::class, 'index'])->name('schemes.index');
    Route::get('/create', [\Modules\Scheme\Http\Controllers\SchemeController::class, 'create'])->name('schemes.create');
    Route::post('/', [\Modules\Scheme\Http\Controllers\SchemeController::class, 'store'])->name('schemes.store');
    Route::get('/{id}', [\Modules\Scheme\Http\Controllers\SchemeController::class, 'show'])->name('schemes.show');
    Route::get('/{id}/edit', [\Modules\Scheme\Http\Controllers\SchemeController::class, 'edit'])->name('schemes.edit');
    Route::put('/{id}', [\Modules\Scheme\Http\Controllers\SchemeController::class, 'update'])->name('schemes.update');
    Route::delete('/{id}', [\Modules\Scheme\Http\Controllers\SchemeController::class, 'destroy'])->name('schemes.destroy');
});
