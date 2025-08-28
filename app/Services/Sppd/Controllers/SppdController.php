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

use App\Services\Employee\Model\Employee;
use Illuminate\Support\Facades\DB;

class SppdController extends Controller
{
    public function index()
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $query = Sppd::with(['approvals', 'files', 'histories', 'expenses']);

        // Jika bukan admin → hanya bisa lihat punya sendiri
        if (!auth()->user()->hasRole('admin')) {
            $query->where('user_id', auth()->id());
        }

        $sppds = $query->latest()->get();

        return response()->json([
            'data' => $sppds,
        ], 200);
    }

    public function show($id)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $sppd = Sppd::with(['user','approvals', 'files', 'histories', 'expenses'])->findOrFail($id);

        // Cek kalau user bukan admin, hanya boleh lihat miliknya
        if (!auth()->user()->hasRole('admin') && $sppd->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => $sppd,
        ], 200);
    }

    public function store(Request $request)
    {
        
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'userid' => 'required',
            'tujuan' => 'required|string',
            'lokasi_tujuan' => 'required|string',
            'tanggal_berangkat' => 'required|date',
            'tanggal_pulang' => 'required|date|after_or_equal:tanggal_berangkat',
            'transportasi' => 'nullable|string',
            'biaya_estimasi' => 'nullable|numeric',
            'files' => 'nullable|array',
            'expenses' => 'nullable|array',
        ]);

        $user = auth()->user();
        $employee = Employee::where('user_id',$request->userid)->with('position', 'company')->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee data not found'], 404);
        }
        DB::transaction(function() use ($request, &$sppd, $employee) {

            $companyCode = strtoupper($employee->company->id ?? 'XXX');

            $lastSppd = Sppd::whereYear('created_at', date('Y'))
                ->whereMonth('created_at', date('m'))
                ->orderBy('id', 'desc')
                ->first();

            $lastNumber = 0;
            if ($lastSppd && preg_match('/^(\d+)/', $lastSppd->nomor_sppd, $matches)) {
                $lastNumber = (int) $matches[1];
            }

            $newNumber  = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            $bulan      = date('m');
            $tahun      = date('y');
            $nomorSppd  = "{$newNumber}/{$companyCode}-SPPD/{$bulan}/{$tahun}";
            // 1. Simpan SPPD utama
            $sppd = Sppd::create([
                'nomor_sppd' => $nomorSppd,
                'user_id' => $employee->user_id,
                'tujuan' => $request->tujuan,
                'lokasi_tujuan' => $request->lokasi_tujuan,
                'tanggal_berangkat' => $request->tanggal_berangkat,
                'tanggal_pulang' => $request->tanggal_pulang,
                'transportasi' => $request->transportasi,
                'biaya_estimasi' => $request->biaya_estimasi,
                'status' => 'Pending'
            ]);

            // 2. Cari Approval Flow berdasarkan company + position
            $flow = \App\Services\Sppd\Model\ApprovalFlow::where('company_id', $employee->company_id)
                ->where('requester_position_id', $employee->position_id)
                ->where('is_active', 1)
                ->first();
           
            if ($flow) {
                $steps = $flow->getApprovalSteps($employee->position_id ?? 0, $request->biaya_estimasi ?? 0);
                foreach ($steps as $step) {
                    SppdApproval::create([
                        'sppd_id' => $sppd->id,
                        'approver_id' => $step->user_id,
                        'role' => $step->role ?? 'Approver',
                        'status' => 'Pending'
                    ]);
                }
            }

            // 3. Simpan Files
            // if ($request->has('files')) {
            //     foreach ($request->files as $file) {
            //         SppdFile::create([
            //             'sppd_id' => $sppd->id,
            //             'jenis_file' => $file['jenis_file'],
            //             'file_path' => $file['file_path'],
            //             'uploaded_by' => $employee->user_id,
            //             'uploaded_at' => now()
            //         ]);
            //     }
            // }

            // 4. Simpan History
            SppdHistory::create([
                'sppd_id' => $sppd->id,
                'user_id' => $employee->user_id,
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

        try {
            // Kirim email ke User
            \Mail::to($employee->user->email)
                ->send(new \App\Mail\SppdSubmittedMail($sppd));

            // Kirim email ke Atasan (dari approvals)
            $approvals = $sppd->approvals()->with('approver')->get();
            foreach ($approvals as $approval) {
                if ($approval->approver && $approval->approver->email) {
                    \Mail::to($approval->approver->email)
                        ->send(new \App\Mail\SppdApprovalRequestMail($sppd, $approval->approver));
                }
            }

            } catch (\Exception $e) {
                report($e); 
            }

        return response()->json([
            'message' => 'SPPD berhasil dibuat dengan approval otomatis',
            'sppd' => $sppd->load(['approvals', 'files', 'histories', 'expenses'])
        ], 201);
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
