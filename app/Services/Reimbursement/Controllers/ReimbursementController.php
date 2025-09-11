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
use App\Services\Employee\Model\Employee;

class ReimbursementController extends Controller
{
    /**
     * List semua reimbursement milik user (atau semua jika admin/finance).
     */
    public function index()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        if (in_array($user->role, ['admin', 'finance'])) {
            $data = Reimbursement::with(['user', 'sppd', 'approvals', 'files'])->get();
        } else {
            $data = Reimbursement::with(['sppd', 'approvals', 'files'])
                ->where('user_id', $user->id)
                ->get();
        }

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Store reimbursement baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sppd_id'     => 'required|exists:sppds,id',
            'category_id'     => 'required|exists:reimbursement_category,id',
            'title'       => 'required|string|max:100',
            'description' => 'nullable|string',
            'amount'      => 'required|numeric|min:0',
            'files.*'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->with('position', 'company')->first();

        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $data = $validator->validated();
            $data['user_id'] = $user->id;
            $data['status'] = 'SUBMITTED';

                        
            $already = Reimbursement::where('sppd_id', $data['sppd_id'])
                ->whereNotIn('status', ['REJECTED']) // kalau REJECTED boleh ajukan ulang
                ->exists();

            if ($already) {
                return response()->json([
                    'message' => 'SPPD ini sudah ada klaim aktif (bukan REJECTED).'
                ], 422);
            }

            
            $reimbursement = Reimbursement::create($data);

            // Upload files jika ada
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('uploads/reimbursements', 'public');
                    ReimbursementFile::create([
                        'reimbursement_id' => $reimbursement->id,
                        'file_path'        => $path,
                        'file_type'        => $file->getClientOriginalExtension(),
                        'uploaded_by'      => $user->id,
                    ]);
                }
            }

            $flow = \App\Services\Sppd\Model\ApprovalFlow::where('company_id', $employee->company_id)
                ->where('requester_position_id', $employee->position_id)
                ->where('is_active', 1)
                ->first();
            
            if ($flow) {
                $steps = $flow->getApprovalSteps($employee->position_id ?? 0, $request->biaya_estimasi ?? 0);
                foreach ($steps as $step) {
                    ReimbursementApproval::create([
                        'reimbursement_id'     => $reimbursement->id,
                        'approved_by' => $step->user_id,
                        'notes'        => 'Notes',
                        'status'      => 'Pending'
                    ]);
                }
            }

            return response()->json([
                'message' => 'Reimbursement berhasil dibuat',
                'data'    => $reimbursement->load(['files']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan reimbursement: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update reimbursement (hanya pemilik jika masih DRAFT, atau admin).
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'          => 'required|integer|exists:reimbursements,id',
            'category_id'     => 'required|exists:reimbursement_category,id',
            'title'       => 'required|string|max:100',
            'description' => 'nullable|string',
            'amount'      => 'required|numeric|min:0',
            'status'      => ['nullable', Rule::in(['DRAFT','SUBMITTED','APPROVED','REJECTED','PAID'])],
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
            $data = $validator->validated();
            $reimbursement = Reimbursement::findOrFail($data['id']);

            // cek hak akses
            if ($reimbursement->user_id !== $user->id && !in_array($user->role, ['admin','finance'])) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            $reimbursement->update($data);

            return response()->json([
                'message' => 'Reimbursement berhasil diperbarui',
                'data'    => $reimbursement,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal update reimbursement: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cari reimbursement berdasarkan ID.
     */
    public function findById($id)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $reimbursement = Reimbursement::with(['sppd','user','approvals','files'])->findOrFail($id);

            // cek hak akses
            if ($reimbursement->user_id !== $user->id && !in_array($user->role, ['admin','finance'])) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            return response()->json([
                'message' => 'Data ditemukan',
                'data'    => $reimbursement,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Data tidak ditemukan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Hapus reimbursement.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:reimbursements,id',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $reimbursement = Reimbursement::findOrFail($request->id);

            if ($reimbursement->user_id !== $user->id && !in_array($user->role, ['admin','finance'])) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            $reimbursement->delete();

            return response()->json(['message' => 'Data berhasil dihapus'], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
        }
    }
}
