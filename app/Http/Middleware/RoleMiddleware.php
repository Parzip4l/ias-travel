<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Contoh penggunaan:
     * Route::middleware(['auth:api', RoleMiddleware::class . ':admin'])->group(...);
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json(['error' => 'Unauthorized. Insufficient role access.'], 403);
        }

        return $next($request);
    }
}
