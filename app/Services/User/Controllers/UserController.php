<?php

namespace App\Services\User\Controllers;

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
use App\Services\User\Model\Role;

class UserController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        $data = User::all();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function role()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        $role = Role::all();

        return response()->json([
            'data' => $role,
        ]);
    }

    public function storeRole(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'name' => 'required|string|max:15|unique:roles,name',
        ]);

        // Cek autentikasi user
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            // Simpan role baru
            $role = Role::create([
                'name' => $validated['name'],
            ]);

            return response()->json([
                'message' => 'Role berhasil dibuat',
                'data' => $role,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan role: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateRole(Request $request)
    {
        // Validasi input, id wajib dan name wajib, unik kecuali sendiri
        $validated = $request->validate([
            'id' => 'required|integer|exists:roles,id',
            'name' => 'required|string|max:15|unique:roles,name,' . $request->id,
        ]);

        // Cek autentikasi user
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            // Cari role berdasarkan id
            $role = Role::findOrFail($validated['id']);

            // Update name
            $role->name = $validated['name'];
            $role->save();

            return response()->json([
                'message' => 'Role berhasil diperbarui',
                'data' => $role,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui role: ' . $e->getMessage(),
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
            $role = Role::findOrFail($id);

            return response()->json([
                'message' => 'Data ditemukan',
                'data' => $role,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Data tidak ditemukan: ' . $e->getMessage()], 500);
        }
    }

    public function deleteRoles(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:roles,id',
        ]);
        
        // Cek autentikasi user
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            $role = Role::findOrFail($validated['id']);
            $role->delete();

            return response()->json([
                'message' => 'Role berhasil dihapus',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus role: ' . $e->getMessage(),
            ], 500);
        }
    }
}
