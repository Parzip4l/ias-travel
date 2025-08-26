<?php

namespace App\Services\Sppd\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\Sppd\Model\Sppd;
use App\Services\Sppd\Model\SppdApproval;
use App\Services\Sppd\Model\SppdFile;
use App\Services\Sppd\Model\SppdHistory;
use App\Services\Sppd\Model\SppdExpense;
use Illuminate\Support\Facades\DB;

class SppdController extends Controller
{
    public function index()
    {
        $sppds = Sppd::with(['approvals', 'files', 'histories', 'expenses'])->get();
        return response()->json([
            'data' => $sppds,
        ], 201);
    }

    public function show($id)
    {
        $sppd = Sppd::with(['user','approvals', 'files', 'histories', 'expenses'])->findOrFail($id);
        return response()->json([
            'data' => $sppd,
        ], 201);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'tujuan' => 'required|string',
            'lokasi_tujuan' => 'required|string',
            'tanggal_berangkat' => 'required|date',
            'tanggal_pulang' => 'required|date|after_or_equal:tanggal_berangkat',
            'transportasi' => 'nullable|string',
            'biaya_estimasi' => 'nullable|numeric',
            'approvals' => 'nullable|array', // array of approver_id & role
            'files' => 'nullable|array',     // array of jenis_file & file_path
            'expenses' => 'nullable|array',  // array of kategori, deskripsi, jumlah, bukti_file
        ]);

        DB::transaction(function() use ($request, &$sppd) {
            // 1. Simpan SPPD utama
            $sppd = Sppd::create($request->only([
                'user_id', 'tujuan', 'lokasi_tujuan', 
                'tanggal_berangkat', 'tanggal_pulang', 
                'transportasi', 'biaya_estimasi'
            ]));

            // 2. Simpan Approvals
            if ($request->has('approvals')) {
                foreach ($request->approvals as $approval) {
                    SppdApproval::create([
                        'sppd_id' => $sppd->id,
                        'approver_id' => $approval['approver_id'],
                        'role' => $approval['role'],
                        'status' => 'Pending'
                    ]);
                }
            }

            // 3. Simpan Files (tiket/hotel/lampiran)
            if ($request->has('files')) {
                foreach ($request->files as $file) {
                    SppdFile::create([
                        'sppd_id' => $sppd->id,
                        'jenis_file' => $file['jenis_file'],
                        'file_path' => $file['file_path'],
                        'uploaded_by' => $request->user_id,
                        'uploaded_at' => now()
                    ]);
                }
            }

            // 4. Simpan History
            SppdHistory::create([
                'sppd_id' => $sppd->id,
                'user_id' => $request->user_id,
                'status_awal' => 'Draft',
                'status_akhir' => 'Pending',
                'catatan' => 'Pengajuan awal'
            ]);

            // 5. Simpan Expenses
            if ($request->has('expenses')) {
                foreach ($request->expenses as $expense) {
                    SppdExpense::create([
                        'sppd_id' => $sppd->id,
                        'kategori' => $expense['kategori'],
                        'deskripsi' => $expense['deskripsi'] ?? null,
                        'jumlah' => $expense['jumlah'] ?? 0,
                        'bukti_file' => $expense['bukti_file'] ?? null
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'SPPD berhasil dibuat beserta approvals, files, history, dan expenses',
            'sppd' => $sppd->load(['approvals', 'files', 'histories', 'expenses'])
        ]);
    }

    public function update(Request $request, $id)
    {
        $sppd = Sppd::findOrFail($id);
        $sppd->update($request->all());
        return response()->json(['message' => 'SPPD berhasil diupdate', 'sppd' => $sppd]);
    }

    public function destroy($id)
    {
        $sppd = Sppd::findOrFail($id);
        $sppd->delete();
        return response()->json(['message' => 'SPPD berhasil dihapus']);
    }

    
}
