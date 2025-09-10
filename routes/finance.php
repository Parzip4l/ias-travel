<?php 

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Middleware\RoleMiddleware;
use App\Models\User;

// Controller
use App\Services\Finance\Controllers\FinanceController;
use Illuminate\Support\Facades\Http;


Route::prefix('finance')->group(function () {
    Route::get('index', [FinanceController::class, 'index'])->name('finance.index');
});


