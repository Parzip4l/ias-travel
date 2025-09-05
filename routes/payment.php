<?php 

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Middleware\RoleMiddleware;
use App\Models\User;

// Controller
use App\Services\Payment\Controllers\PaymentController;
use Illuminate\Support\Facades\Http;


Route::prefix('payment')->group(function () {
    Route::post('create', [PaymentController::class, 'create'])->name('payment.create');
    Route::post('webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');
});


