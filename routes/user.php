<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Services\User\Controllers\UserController;
use App\Services\User\Controllers\DivisiController;
use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Support\Facades\Password;

/*
|--------------------------------------------------------------------------
| Role-Based Protected Routes
|--------------------------------------------------------------------------
*/

Route::prefix('user')->group(function () {
    Route::middleware(['auth:api', RoleMiddleware::class . ':admin'])->group(function () {
        Route::get('/user-list', [UserController::class, 'index']);
        Route::get('/role-list', [UserController::class, 'role']);
        Route::post('/role-create', [UserController::class, 'storeRole']);
        Route::post('/role-update', [UserController::class, 'updateRole']);
        Route::post('/role-delete', [UserController::class, 'deleteRoles']);
        Route::get('/find-role/{id}', [UserController::class, 'findbyId'])->name('roles.find');
    });
});

// Divisi
Route::prefix('divisi')->group(function () {
    Route::middleware(['auth:api', RoleMiddleware::class . ':admin'])->group(function () {
        Route::get('/list', [DivisiController::class, 'index']);
        Route::post('/store', [DivisiController::class, 'store']);
        Route::post('/update', [DivisiController::class, 'update']);
        Route::post('/delete', [DivisiController::class, 'delete']);
        Route::get('/divisi-single/{id}', [DivisiController::class, 'findbyId'])->name('divisi.find');
    });
});