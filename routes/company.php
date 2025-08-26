<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Middleware\RoleMiddleware;

// Controller
use App\Services\Company\Controllers\CompanyTypeController;
use App\Services\Company\Controllers\CompanyController;

/*
|--------------------------------------------------------------------------
| Role-Based Protected Routes
|--------------------------------------------------------------------------
*/

Route::prefix('company-type')->group(function () {
    Route::middleware(['auth:api', RoleMiddleware::class . ':admin'])->group(function () {
        Route::get('/list', [CompanyTypeController::class, 'index']);
        Route::post('/store', [CompanyTypeController::class, 'store']);
        Route::post('/update', [CompanyTypeController::class, 'update']);
        Route::post('/delete', [CompanyTypeController::class, 'delete']);
        Route::get('/single/{id}', [CompanyTypeController::class, 'findbyId'])->name('single.company');
    });
});

Route::prefix('company')->group(function () {
    Route::middleware(['auth:api', RoleMiddleware::class . ':admin'])->group(function () {
        Route::get('/list', [CompanyController::class, 'index']);
        Route::post('/store', [CompanyController::class, 'store']);
        Route::post('/update', [CompanyController::class, 'update']);
        Route::post('/delete', [CompanyController::class, 'delete']);
        Route::get('/single/{id}', [CompanyController::class, 'findbyId'])->name('single.company');

        Route::get('/export-template', [CompanyController::class, 'exportTemplate']);
        Route::get('/export-data', [CompanyController::class, 'exportData']);
        Route::post('/import', [CompanyController::class, 'import']);
    });
});