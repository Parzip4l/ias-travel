<?php

namespace App\Services\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

// Event
use Illuminate\Auth\Events\Registered;
use App\Notifications\CustomVerifyEmail;

// Models
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        // Cek user berdasarkan email
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return response()->json(['error' => 'Email not registered.'], 404);
        }

        // Cek password manual
        if (!Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Incorrect password.'], 401);
        }

        // Cek verifikasi email
        if (!$user->hasVerifiedEmail()) {
            return response()->json(['error' => 'Email not verified. Please verify your email first.'], 403);
        }

        // Generate token setelah semua validasi
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'access_token' => $token,
            'user' => $user,
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }


    public function me()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        return response()->json($user);
    }

    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:user,admin,finance',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $user->notify(new CustomVerifyEmail());

        return response()->json(['message' => 'User registered successfully. Please verify your email.'], 201);
    }
}
