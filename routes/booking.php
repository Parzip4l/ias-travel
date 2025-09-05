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

Route::prefix('flights')->group(function () {
    Route::get('search', [App\Services\Booking\Controllers\FlightController::class, 'search']);
});

Route::prefix('hotels')->group(function () {
    Route::get('geo', [App\Services\Booking\Controllers\HotelController::class, 'searchByGeo']);
});