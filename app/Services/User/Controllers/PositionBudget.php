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
use App\Services\User\Model\PositionsBudget;

class PositionBudget extends Controller
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

        $data = PositionsBudget::all();
        $data->load(['position', 'category']);

        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'position_id' => 'required|exists:positions,id',
            'category_id' => 'required|exists:budget_categories,id',
            'type' => 'required|string|max:55',
            'max_budget' => 'required',
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

            $budget = PositionsBudget::create([
                'position_id' => $request->position_id,
                'category_id' => $request->category_id,
                'type' => $request->type,
                'max_budget' => $request->max_budget,
            ]);

            $budget->load(['position', 'category']);

            return response()->json([
                'message' => 'Budget berhasil dibuat',
                'data' => $budget,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan budget: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:positions_budgets,id',
            'position_id' => 'required|exists:positions,id',
            'category_id' => 'required|exists:budget_categories,id',
            'type' => 'required|string|max:55',
            'max_budget' => 'required',
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
            $budget = PositionsBudget::findOrFail($request->id);

            // Update data
            $budget->position_id = $request->position_id;
            $budget->category_id = $request->category_id;
            $budget->type = $request->type;
            $budget->max_budget = $request->max_budget;
            $budget->save();

            $budget->load(['position', 'category']);

            return response()->json([
                'message' => 'budget berhasil diperbarui',
                'data' => $budget,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui budget: ' . $e->getMessage(),
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
            $budget = PositionsBudget::findOrFail($id);
            $budget->load(['position', 'category']);

            return response()->json([
                'message' => 'Data ditemukan',
                'data' => $budget,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Data tidak ditemukan: ' . $e->getMessage()], 500);
        }
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:positions_budgets,id',
        ]);
        
        // Cek autentikasi user
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            $budget = PositionsBudget::findOrFail($validated['id']);
            $budget->delete();

            return response()->json([
                'message' => 'budget berhasil dihapus',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus budget: ' . $e->getMessage(),
            ], 500);
        }
    }
}
