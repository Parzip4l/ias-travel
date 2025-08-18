<?php

namespace App\Services\User\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// Event
use Illuminate\Auth\Events\Registered;
use App\Notifications\CustomVerifyEmail;

// Models
use App\Models\User;
use App\Services\User\Model\Position;

class PositionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        if ($user->role != 'admin')
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);

        $data = Position::all();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:55|unique:positions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Cek autentikasi user
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            // Simpan role baru
            $jabatan = Position::create([
                'name' => $request->name
            ]);

            return response()->json([
                'message' => 'Jabatan berhasil dibuat',
                'data' => $jabatan,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan jabatan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:positions,id',
            'name' => [
                'required',
                'string',
                'max:15',
                Rule::unique('positions', 'name')->ignore($request->id)
            ]
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Cek autentikasi user
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            // Cari divisi berdasarkan ID
            $jabatan = Position::findOrFail($request->id);

            // Update data
            $jabatan->name = $request->name;
            $jabatan->save();

            return response()->json([
                'message' => 'jabatan berhasil diperbarui',
                'data' => $jabatan,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui jabatan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function findbyId($id)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $jabatan = Position::findOrFail($id);

            return response()->json([
                'message' => 'Data ditemukan',
                'data' => $jabatan,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Data tidak ditemukan: ' . $e->getMessage()], 500);
        }
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:positions,id',
        ]);
        
        // Cek autentikasi user
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            $jabatan = Position::findOrFail($validated['id']);
            $jabatan->delete();

            return response()->json([
                'message' => 'jabatan berhasil dihapus',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus jabatan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
