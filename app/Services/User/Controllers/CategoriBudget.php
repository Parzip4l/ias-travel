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
use App\Services\User\Model\BudgetCategory;

class CategoriBudget extends Controller
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

        $data = BudgetCategory::all();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:55|unique:budget_categories,name',
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
            $category = BudgetCategory::create([
                'name' => $request->name
            ]);

            return response()->json([
                'message' => 'Kategori berhasil dibuat',
                'data' => $category,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan Kategori: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:budget_categories,id',
            'name' => [
                'required',
                'string',
                'max:15',
                Rule::unique('budget_categories', 'name')->ignore($request->id)
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
            $category = BudgetCategory::findOrFail($request->id);

            // Update data
            $category->name = $request->name;
            $category->save();

            return response()->json([
                'message' => 'kategori berhasil diperbarui',
                'data' => $category,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui kategori: ' . $e->getMessage(),
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
            $category = BudgetCategory::findOrFail($id);

            return response()->json([
                'message' => 'Data ditemukan',
                'data' => $category,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Data tidak ditemukan: ' . $e->getMessage()], 500);
        }
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:budget_categories,id',
        ]);
        
        // Cek autentikasi user
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            $kategori = BudgetCategory::findOrFail($validated['id']);
            $kategori->delete();

            return response()->json([
                'message' => 'kategori berhasil dihapus',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus kategori: ' . $e->getMessage(),
            ], 500);
        }
    }
}
