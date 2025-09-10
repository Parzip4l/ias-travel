<?php

namespace App\Services\Finance\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

// Event
use Illuminate\Auth\Events\Registered;
use App\Notifications\CustomVerifyEmail;

// Models
use App\Models\User;
use App\Services\Company\Model\CompanyType;
use App\Services\Company\Model\Company;
use App\Services\Payment\Model\Payment;

// Import Export
use App\Services\Company\Exports\CompanyTemplateExport;
use App\Services\Company\Exports\CompanyExport;
use App\Services\Company\Imports\CompanyImport;

class FinanceController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        if (!in_array($user->role, ['admin', 'finance'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Ambil semua payment + relasi SPPD & user
        $payments = Payment::with(['sppd.user'])->get();

        // === 1. KPI Summary ===
        $totalPengeluaran = $payments->where('status', 'PAID')->sum('amount');
        $totalOutstanding = $payments->whereIn('status', ['PENDING'])->sum('amount');
        $totalDigital = $payments->where('status', 'PAID')->where('payment_type', 'digital')->sum('amount');
        $totalReimbursement = $payments->where('status', 'WAITING_INVOICE')->where('payment_type', 'invoicing')->sum('amount');

        // === 2. Tren bulanan (dipisah digital & reimbursement) ===
        $monthlyTrends = Payment::selectRaw("
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(CASE 
                    WHEN payment_type = 'digital' AND status = 'PAID' 
                    THEN amount ELSE 0 END
            ) as digital,
            SUM(CASE 
                    WHEN payment_type = 'invoicing' AND status IN ('PAID', 'WAITING_INVOICE') 
                    THEN amount ELSE 0 END
            ) as invoicing
        ")
        ->groupBy('month')
        ->orderBy('month', 'asc')
        ->get();

        // === 3. Breakdown by departemen ===
        $byDepartment = Payment::selectRaw("users.divisi_id as dept, SUM(payments.amount) as total")
            ->join('sppds', 'payments.sppd_id', '=', 'sppds.id')
            ->join('users', 'sppds.user_id', '=', 'users.id')
            ->where('payments.status', 'PAID')
            ->groupBy('users.divisi_id')
            ->get();

        return response()->json([
            'summary' => [
                'total_pengeluaran' => $totalPengeluaran,
                'total_outstanding' => $totalOutstanding,
                'total_digital' => $totalDigital,
                'total_reimbursement' => $totalReimbursement,
                'count_paid' => $payments->where('status', 'PAID')->count(),
                'count_pending' => $payments->where('status', 'PENDING')->count(),
                'count_waiting_invoice' => $payments->where('status', 'WAITING_INVOICE')->count(),
            ],
            'trends' => $monthlyTrends,
            'by_department' => $byDepartment,
            'list' => $payments, // detail untuk tabel
        ]);
    }

}
