<?php

namespace App\Services\Sppd\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Sppd\Model\ApprovalAmountFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class ApprovalAmountC extends Controller
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
     * List all amount flows
     */
    public function index()
    {
        if ($resp = $this->authorizeAdmin()) return $resp;

        try {
            $flows = ApprovalAmountFlow::with('flow','steps')->get();
            return response()->json($flows, 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch data.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store new amount flow
     */
    public function store(Request $request)
    {
        if ($resp = $this->authorizeAdmin()) return $resp;

        $validator = Validator::make($request->all(), [
            'approval_flow_id' => 'required|exists:approval_flows,id',
            'min_amount'       => 'required|numeric|min:0',
            'max_amount'       => 'required|numeric|gte:min_amount',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $flow = ApprovalAmountFlow::create($validator->validated());
            return response()->json([
                'message' => 'Approval Amount Flow created successfully.',
                'data'    => $flow
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to create record.',
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
            $flow = ApprovalAmountFlow::with('flow','steps')->findOrFail($id);
            return response()->json($flow, 200);
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
            'approval_flow_id' => 'sometimes|required|exists:approval_flows,id',
            'min_amount'       => 'sometimes|required|numeric|min:0',
            'max_amount'       => 'sometimes|required|numeric|gte:min_amount',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $flow = ApprovalAmountFlow::findOrFail($id);
            $flow->update($validator->validated());

            return response()->json([
                'message' => 'Approval Amount Flow updated successfully.',
                'data'    => $flow
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
            $flow = ApprovalAmountFlow::findOrFail($id);
            $flow->delete();

            return response()->json([
                'message' => 'Approval Amount Flow deleted successfully.'
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
            $flows = ApprovalAmountFlow::where('approval_flow_id', $idflow)->with('flow','steps')->get();
            return response()->json($flows, 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch data.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
