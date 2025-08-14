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

/*
|--------------------------------------------------------------------------
| Role-Based Protected Routes
|--------------------------------------------------------------------------
*/

Route::prefix('sppd')->group(function () {
    Route::get('/list', [SppdController::class, 'index']);
    Route::get('/details/{id}', [SppdController::class, 'show']);
});
