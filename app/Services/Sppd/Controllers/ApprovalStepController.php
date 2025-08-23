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
use App\Services\Sppd\Model\ApprovalStep;

class ApprovalStepController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        if ($user->role != 'admin') {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        $data = ApprovalStep::all();
        $data->load(['flow','division', 'position']); // eager load relasi
        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'approval_flow_id' => 'required|exists:approval_flows,id',
            'step_order'       => 'required|integer',
            'user_id'          => 'nullable|exists:users,id',
            'division_id'      => 'nullable|exists:divisions,id',
            'position_id'      => 'nullable|exists:positions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $step = ApprovalStep::create($validator->validated());

            return response()->json([
                'message' => 'Data berhasil dibuat',
                'data'    => $step,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'              => 'required|exists:approval_steps,id',
            'approval_flow_id'=> 'required|exists:approval_flows,id',
            'step_order'      => 'required|integer',
            'user_id'         => 'nullable|exists:users,id',
            'division_id'     => 'nullable|exists:divisions,id',
            'position_id'     => 'nullable|exists:positions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $step = ApprovalStep::findOrFail($request->id);
            $step->update($validator->validated());

            return response()->json([
                'message' => 'Data berhasil diperbarui',
                'data'    => $step,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function findByIdFlow($flowid)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $steps = ApprovalStep::where('approval_flow_id', $flowid)
                ->orderBy('step_order', 'asc')
                ->with(['flow.requesterPosition','division','position','user'])
                ->get();

            // Map biar keluar requester_position_name langsung
            $steps = $steps->map(function ($step) {
                return [
                    'id' => $step->id,
                    'approval_flow_id' => $step->approval_flow_id,
                    'step_order' => $step->step_order,
                    'user' => $step->user,
                    'division' => $step->division,
                    'position' => $step->position,
                    'role' => $step->role,
                    'is_final' => $step->is_final,
                    'flow' => [
                        'id' => $step->flow->id,
                        'name' => $step->flow->name,
                        'company_id' => $step->flow->company_id,
                        'requester_position_id' => $step->flow->requester_position_id,
                        'requester_position_name' => $step->flow->requesterPosition->name ?? null,
                    ],
                ];
            });

            return response()->json([
                'message' => 'Data ditemukan',
                'data'    => $steps,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Data tidak ditemukan: ' . $e->getMessage()], 500);
        }
    }

    public function findById($id)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $step = ApprovalStep::findOrFail($id);
            $step->load(['flow','division', 'position']);
            return response()->json([
                'message' => 'Data ditemukan',
                'data'    => $step,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Data tidak ditemukan: ' . $e->getMessage()], 500);
        }
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:approval_steps,id',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            $step = ApprovalStep::findOrFail($validated['id']);
            $step->delete();

            return response()->json([
                'message' => 'Data berhasil dihapus',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function toggleFinal(Request $request, $id)
    {
        $step = ApprovalStep::findOrFail($id);
        $step->is_final = $request->is_final;
        $step->save();

        return response()->json(['success' => true, 'is_final' => $step->is_final]);
    }
}