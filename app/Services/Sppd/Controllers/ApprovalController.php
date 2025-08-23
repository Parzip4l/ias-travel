<?php

namespace App\Services\Sppd\Controllers;

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
use App\Services\Sppd\Model\ApprovalFlow;

class ApprovalController extends Controller
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

        $data = ApprovalFlow::all();
        $data->load(['company','requesterPosition']);
        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'company_id'        => 'required|string|max:50|exists:companies,id',
            'name'              => 'required|string',
            'is_active'         => 'required',
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

            // Simpan company baru
            $approvalFlow = ApprovalFlow::create($data);

            return response()->json([
                'message' => 'Data berhasil dibuat',
                'data' => $approvalFlow,
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
            'company_id'        => 'required|string|max:50|exists:companies,id',
            'name'              => 'required|string',
            'is_active'         => 'required',
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
            $flow = ApprovalFlow::findOrFail($request->id);
            $data = $validator->validated();

            // Update data
            $flow->update($data);

            return response()->json([
                'message' => 'Data berhasil diperbarui',
                'data'    => $flow,
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
            $flow = ApprovalFlow::findOrFail($id);
            $flow->load(['company','requesterPosition']);
            return response()->json([
                'message' => 'Data ditemukan',
                'data' => $flow,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Data tidak ditemukan: ' . $e->getMessage()], 500);
        }
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:approval_flows,id',
        ]);
        
        // Cek autentikasi user
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            $flow = ApprovalFlow::findOrFail($validated['id']);
            $flow->delete();

            return response()->json([
                'message' => 'Data berhasil dihapus',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function toggleActive(Request $request, $id)
    {
        $flow = ApprovalFlow::findOrFail($id);
        $flow->is_active = $request->is_active;
        $flow->save();

        return response()->json(['success' => true, 'is_active' => $flow->is_active]);
    }

    
}
