<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any authentication / authorization services.
     */
    public function boot(): void
    {
        // Custom URL for password reset email
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return URL::temporarySignedRoute(
                'password.reset',
                now()->addMinutes(5),
                [
                    'token' => $token,
                    'email' => $user->getEmailForPasswordReset(),
                ]
            );
        });
    }
}
