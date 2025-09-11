<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Exceptions\AuthExceptionHandler;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: [
            __DIR__.'/../routes/auth.php',
            __DIR__.'/../routes/user.php',
            __DIR__.'/../routes/sppd.php',
            __DIR__.'/../routes/finance.php',
            __DIR__.'/../routes/booking.php',
            __DIR__.'/../routes/product.php',
            __DIR__.'/../routes/payment.php',
            __DIR__.'/../routes/document.php',
            __DIR__.'/../routes/notification.php',
            __DIR__.'/../routes/report.php',
            __DIR__.'/../routes/company.php',
            __DIR__.'/../routes/reimbursement.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(
            fn (AuthenticationException $e, $request) => 
                (new AuthExceptionHandler())->handle($e, $request)
        );
    })->create();
