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
use App\Services\Auth\Controllers\AuthController;
use App\Services\Sppd\Controllers\SppdController;
use App\Services\Sppd\Controllers\ApprovalController;
use App\Services\Sppd\Controllers\ApprovalStepController;
use App\Services\Sppd\Controllers\ApprovalAmountC;
use App\Services\Sppd\Controllers\ApprovalAmountStepController;

/*
|--------------------------------------------------------------------------
| Role-Based Protected Routes
|--------------------------------------------------------------------------
*/

Route::prefix('sppd')->group(function () {
    Route::get('/list', [SppdController::class, 'index']);
    Route::get('/details/{id}', [SppdController::class, 'show']);
});

Route::prefix('approval')->group(function () {
    Route::prefix('flow')->group(function () {
        Route::middleware(['auth:api', RoleMiddleware::class . ':admin'])->group(function () {
            Route::get('/list', [ApprovalController::class, 'index']);
            Route::post('/store', [ApprovalController::class, 'store']);
            Route::post('/update', [ApprovalController::class, 'update']);
            Route::post('/delete', [ApprovalController::class, 'delete']);
            Route::get('/single/{id}', [ApprovalController::class, 'findbyId'])->name('single.flow');
            Route::post('/active-flow/{id}', [ApprovalController::class, 'toggleActive'])->name('flow.final');
        });
    });
    Route::prefix('steps')->group(function () {
        Route::middleware(['auth:api', RoleMiddleware::class . ':admin'])->group(function () {
            Route::get('/list', [ApprovalStepController::class, 'index']);
            Route::post('/store', [ApprovalStepController::class, 'store']);
            Route::post('/update', [ApprovalStepController::class, 'update']);
            Route::post('/delete', [ApprovalStepController::class, 'delete']);
            Route::get('/single/{id}', [ApprovalStepController::class, 'findbyId'])->name('single.steps');
            Route::post('/final-step/{id}', [ApprovalStepController::class, 'toggleFinal'])->name('single.final');
            Route::get('/all-step/{flowid}', [ApprovalStepController::class, 'findByIdFlow'])->name('steps.all');
        });
    });

    Route::prefix('amount-flow')->group(function () {
        Route::get('/list', [ApprovalAmountC::class, 'index']);
        Route::post('/store', [ApprovalAmountC::class, 'store']);
        Route::post('/update/{id}', [ApprovalAmountC::class, 'update']);
        Route::post('/delete/{id}', [ApprovalAmountC::class, 'destroy']);
        Route::get('/single/{id}', [ApprovalAmountC::class, 'show'])->name('single.flow');
        Route::get('/by-flow/{idflow}', [ApprovalAmountC::class, 'byIdFlow']);
    });

    Route::prefix('amount-step')->group(function () {
        Route::get('/list', [ApprovalAmountStepController::class, 'index']);
        Route::post('/store', [ApprovalAmountStepController::class, 'store']);
        Route::post('/update/{id}', [ApprovalAmountStepController::class, 'update']);
        Route::post('/delete/{id}', [ApprovalAmountStepController::class, 'destroy']);
        Route::get('/single/{id}', [ApprovalAmountStepController::class, 'show'])->name('single.flow');
        Route::get('/by-flow/{idflow}', [ApprovalAmountStepController::class, 'byIdFlow']);
    });
    
});