<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Handle unauthenticated exception.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'message' => 'Unauthenticated. Token is missing or invalid.'
        ], 401);
    }
}
