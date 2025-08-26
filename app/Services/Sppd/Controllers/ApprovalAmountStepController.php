<?php

namespace App\Services\Sppd\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Sppd\Model\ApprovalAmountStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class ApprovalAmountStepController extends Controller
{
    /**
     * Cek otentikasi user dan role admin
     */
    private function authorizeAdmin()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Forbidden. Only admin can access this resource.'
            ], 403);
        }

        return null;
    }

    /**
     * List all steps
     */
    public function index()
    {
        if ($resp = $this->authorizeAdmin()) return $resp;

        try {
            $steps = ApprovalAmountStep::with(['flow', 'division', 'position'])->get();
            return response()->json($steps, 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch data.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store new step
     */
    public function store(Request $request)
    {
        if ($resp = $this->authorizeAdmin()) return $resp;

        $validator = Validator::make($request->all(), [
            'approval_amount_flow_id' => 'required|exists:approval_amount_flows,id',
            'step_order'              => 'required|integer|min:1',
            'user_id'                 => 'nullable|exists:users,id',
            'division_id'             => 'nullable|exists:divisions,id',
            'position_id'             => 'nullable|exists:positions,id',
            'is_final'                => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $step = ApprovalAmountStep::create($validator->validated());
            return response()->json([
                'message' => 'Approval Amount Step created successfully.',
                'data'    => $step
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to create record.' . $e,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show detail by ID
     */
    public function show($id)
    {
        if ($resp = $this->authorizeAdmin()) return $resp;

        try {
            $step = ApprovalAmountStep::with('flow', 'division', 'position')->findOrFail($id);
            return response()->json($step, 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Record not found.',
                'error'   => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update by ID
     */
    public function update(Request $request, $id)
    {
        if ($resp = $this->authorizeAdmin()) return $resp;

        $validator = Validator::make($request->all(), [
            'approval_amount_flow_id' => 'sometimes|required|exists:approval_amount_flows,id',
            'step_order'              => 'sometimes|required|integer|min:1',
            'user_id'                 => 'nullable|exists:users,id',
            'division_id'             => 'nullable|exists:divisions,id',
            'position_id'             => 'nullable|exists:positions,id',
            'role'                    => 'nullable|string|max:100',
            'is_final'                => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $step = ApprovalAmountStep::findOrFail($id);
            $step->update($validator->validated());

            return response()->json([
                'message' => 'Approval Amount Step updated successfully.',
                'data'    => $step
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to update record.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete by ID
     */
    public function destroy($id)
    {
        if ($resp = $this->authorizeAdmin()) return $resp;

        try {
            $step = ApprovalAmountStep::findOrFail($id);
            $step->delete();

            return response()->json([
                'message' => 'Approval Amount Step deleted successfully.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to delete record.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function byIdFlow($idflow)
    {
        if ($resp = $this->authorizeAdmin()) return $resp;
        try {
            $flows = ApprovalAmountStep::where('approval_amount_flow_id', $idflow)->with('flow', 'division', 'position')->get();
            return response()->json($flows, 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch data.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
