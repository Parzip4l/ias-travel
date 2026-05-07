<?php

namespace App\Services\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
// Event
use Illuminate\Auth\Events\Registered;
use App\Notifications\CustomVerifyEmail;

// Models
use App\Models\User;
use App\Services\Employee\Model\Employee;
use App\Services\User\Model\Departement;
use App\Services\User\Model\Position;
use App\Services\Company\Model\Company;

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

    public function registerByAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|exists:roles,name',
            'divisi_id' => 'required|exists:divisions,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $user = DB::transaction(function () use ($request) {
                $division = Departement::findOrFail($request->divisi_id);
                $companyId = $division->company_id ?: Company::query()->value('id');
                $positionId = null;

                if ($companyId) {
                    $positionId = Position::query()
                        ->where('company_id', $companyId)
                        ->value('id');
                }

                if (!$positionId) {
                    $positionId = Position::query()->value('id');
                }

                if (!$companyId) {
                    throw new \RuntimeException('Company default belum tersedia untuk membuat data karyawan.');
                }

                // Buat user dengan password random (tidak digunakan untuk login)
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make(Str::random(12)),
                    'role' => $request->role,
                    'divisi_id' => $request->divisi_id,
                ]);

                Employee::create([
                    'company_id' => $companyId,
                    'user_id' => $user->id,
                    'employee_number' => 'EMP-' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                    'name' => $user->name,
                    'division_id' => $request->divisi_id,
                    'position_id' => $positionId,
                    'join_date' => now()->toDateString(),
                    'employment_status' => 'permanent',
                ]);

                return $user;
            });
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal membuat user dan karyawan default: ' . $e->getMessage(),
            ], 500);
        }

        // Kirim link untuk set password (menggunakan fitur reset password)
        Password::sendResetLink(['email' => $user->email]);

        return response()->json([
            'message' => 'User created successfully. Link to set password has been sent to email.'
        ], 201);
    }
}
