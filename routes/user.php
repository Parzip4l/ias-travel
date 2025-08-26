<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Support\Facades\Password;

// Controller
use App\Services\User\Controllers\UserController;
use App\Services\User\Controllers\DivisiController;
use App\Services\User\Controllers\PositionController;
use App\Services\User\Controllers\CategoriBudget;
use App\Services\User\Controllers\PositionBudget;
use App\Services\Auth\Controllers\AuthController;
use App\Services\Employee\Controllers\EmployeeController;


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
        // Create User
        Route::post('/create-user', [AuthController::class, 'registerByAdmin']);
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

Route::prefix('posisi')->group(function () {
    Route::middleware(['auth:api', RoleMiddleware::class . ':admin'])->group(function () {
        Route::get('/list', [PositionController::class, 'index']);
        Route::post('/store', [PositionController::class, 'store']);
        Route::post('/update', [PositionController::class, 'update']);
        Route::post('/delete', [PositionController::class, 'delete']);
        Route::get('/single/{id}', [PositionController::class, 'findbyId'])->name('posisi.find');
    });
});

Route::prefix('kategori-budget')->group(function () {
    Route::middleware(['auth:api', RoleMiddleware::class . ':admin'])->group(function () {
        Route::get('/list', [CategoriBudget::class, 'index']);
        Route::post('/store', [CategoriBudget::class, 'store']);
        Route::post('/update', [CategoriBudget::class, 'update']);
        Route::post('/delete', [CategoriBudget::class, 'delete']);
        Route::get('/single/{id}', [CategoriBudget::class, 'findbyId'])->name('kategoriBudget.find');
    });
});

Route::prefix('budget')->group(function () {
    Route::middleware(['auth:api', RoleMiddleware::class . ':admin'])->group(function () {
        Route::get('/list', [PositionBudget::class, 'index']);
        Route::post('/store', [PositionBudget::class, 'store']);
        Route::post('/update', [PositionBudget::class, 'update']);
        Route::post('/delete', [PositionBudget::class, 'delete']);
        Route::get('/single/{id}', [PositionBudget::class, 'findbyId'])->name('kategoriBudget.find');
    });
});

// Employee
Route::prefix('karyawan')->group(function () {
    Route::middleware(['auth:api', RoleMiddleware::class . ':admin'])->group(function () {
        Route::get('/list', [EmployeeController::class, 'index']);
        Route::post('/store', [EmployeeController::class, 'store']);
        Route::post('/update', [EmployeeController::class, 'update']);
        Route::post('/delete', [EmployeeController::class, 'delete']);
        Route::get('/single/{id}', [EmployeeController::class, 'findbyId'])->name('employee.find');
        Route::post('/import', [EmployeeController::class, 'import']);
    });
});