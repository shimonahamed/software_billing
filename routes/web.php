<?php

use Illuminate\Support\Facades\Route;
use SoftwareBilling\Http\Controllers\BillingConfigurationController;

Route::middleware(['web', 'auth'])
    ->prefix('software-billing')
    ->name('software-billing.')
    ->group(function () {
        Route::get('/bill_configuration', [BillingConfigurationController::class,'show',])->name('bill_configuration');
        Route::post('/bill_configuration', [ BillingConfigurationController::class, 'store', ])->name('bill_configuration.store');
    });