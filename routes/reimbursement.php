<?php 

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Middleware\RoleMiddleware;
use App\Models\User;

// Controller
use App\Services\Reimbursement\Controllers\ReimbursementController;
use App\Services\Reimbursement\Controllers\ReimbursementCategoryController;
use Illuminate\Support\Facades\Http;


Route::prefix('reimbursement')->group(function () {
    Route::get('index', [ReimbursementController::class, 'index'])->name('reimbursement.index');
    Route::post('store', [ReimbursementController::class, 'store'])->name('reimbursement.store');
    Route::post('update', [ReimbursementController::class, 'update'])->name('reimbursement.update');
    Route::get('/single/{id}', [ReimbursementController::class, 'findbyId'])->name('reimbursement.single');
    Route::post('delete', [ReimbursementController::class, 'delete'])->name('reimbursement.delete');

    Route::prefix('category')->group(function () {
        Route::get('index', [ReimbursementCategoryController::class, 'index'])->name('reimbursement.category.index');
        Route::post('store', [ReimbursementCategoryController::class, 'store'])->name('reimbursement.category.store');
        Route::get('/single/{id}', [ReimbursementCategoryController::class, 'findbyId'])->name('reimbursement.category.single');
        Route::post('update', [ReimbursementCategoryController::class, 'update'])->name('reimbursement.category.update');
        Route::post('delete', [ReimbursementCategoryController::class, 'delete'])->name('reimbursement.category.delete');
    });
});