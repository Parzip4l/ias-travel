<?php

namespace App\Services\Reimbursement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// Models
use App\Services\Reimbursement\Model\Reimbursement;
use App\Services\Reimbursement\Model\ReimbursementFile;
use App\Services\Reimbursement\Model\ReimbursementApproval;
use App\Services\Reimbursement\Model\ReimbursementCategory;

class ReimbursementCategoryController extends Controller
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

        $data = ReimbursementCategory::all();
        $data->load(['companies']);
        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string|max:100',
            'code'                  => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            $data = $validator->validated();
            $data['company_id'] = $user->company_id;
            // Simpan data baru
            $category = ReimbursementCategory::create($data);

            return response()->json([
                'message' => 'Data berhasil dibuat',
                'data' => $category,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'id'                    => 'required|integer',
            'name'                  => 'required|string|max:100',
            'code'                  => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            $category = ReimbursementCategory::findOrFail($request->id);
            $data = $validator->validated();

            // Update data
            $category->update($data);

            return response()->json([
                'message' => 'Data berhasil diperbarui',
                'data'    => $category,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui data: ' . $e->getMessage(),
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
            $category = ReimbursementCategory::findOrFail($id);
            $category->load(['companies']);
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
            'id' => 'required|integer|exists:reimbursement_category,id',
        ]);
        
        // Cek autentikasi user
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            $category = ReimbursementCategory::findOrFail($validated['id']);
            $category->delete();

            return response()->json([
                'message' => 'Data berhasil dihapus',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
