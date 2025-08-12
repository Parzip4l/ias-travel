<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;

class AuthExceptionHandler
{
    public function handle(AuthenticationException $exception, $request): Response
    {
        return response()->json([
            'message' => 'Unauthenticated. Token is missing or invalid.'
        ], 401);
    }
}
