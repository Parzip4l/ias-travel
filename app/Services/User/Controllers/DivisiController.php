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
use App\Services\User\Model\Departement;

class DivisiController extends Controller
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

        $data = Departement::all();
        $data->load(['company']);
        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:55|unique:divisions,name',
            'head_id' => 'required|string|max:15|exists:users,id',
            'company_id' => 'required|string|max:15|exists:companies,id',
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
            $divisi = Departement::create([
                'name' => $request->name,
                'head_id' => $request->head_id,
                'company_id' => $request->company_id
            ]);

            return response()->json([
                'message' => 'Divisi berhasil dibuat',
                'data' => $divisi,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan divisi: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:divisions,id',
            'name' => [
                'required',
                'string',
                'max:15',
                Rule::unique('divisions', 'name')->ignore($request->id)
            ],
            'head_id' => 'required|string|max:15|exists:users,id',
            'company_id' => 'required|string|max:15|exists:companies,id',
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
            $divisi = Departement::findOrFail($request->id);

            // Update data
            $divisi->name = $request->name;
            $divisi->head_id = $request->head_id;
            $divisi->company_id = $request->company_id;
            $divisi->save();

            return response()->json([
                'message' => 'Divisi berhasil diperbarui',
                'data' => $divisi,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui divisi: ' . $e->getMessage(),
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
            $divisi = Departement::findOrFail($id);

            return response()->json([
                'message' => 'Data ditemukan',
                'data' => $divisi,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Data tidak ditemukan: ' . $e->getMessage()], 500);
        }
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:divisions,id',
        ]);
        
        // Cek autentikasi user
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            $divisi = Departement::findOrFail($validated['id']);
            $divisi->delete();

            return response()->json([
                'message' => 'Divisi berhasil dihapus',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus divisi: ' . $e->getMessage(),
            ], 500);
        }
    }
}
