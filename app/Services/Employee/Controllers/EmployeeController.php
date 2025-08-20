<?php

namespace App\Services\Employee\Controllers;

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
use App\Services\Company\Model\CompanyType;
use App\Services\Company\Model\Company;
use App\Services\User\Model\Departement;
use App\Services\User\Model\Position;
use App\Services\Employee\Model\Employee;

class EmployeeController extends Controller
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

        $data = Employee::all();
        $data->load(['company','user','division','position']);
        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'company_id'        => 'required|exists:companies,id',
            'employee_number'   => 'required|string|max:50|unique:employees,employee_number,NULL,id,company_id,' . $request->company_id,
            'name'              => 'required|string|max:100',
            'division_id'       => 'required|exists:divisions,id',
            'position_id'       => 'required|exists:positions,id',
            'join_date'         => 'nullable|date',
            'end_date'          => 'nullable|date|after_or_equal:join_date',
            'employment_status' => 'required|string|in:permanent,contract,intern,probation',
            'grade_level'       => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Pastikan user login
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            // Simpan employee baru
            $employee = Employee::create($validator->validated());

            return response()->json([
                'message' => 'Employee berhasil dibuat',
                'data'    => $employee,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan employee: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        // Cari employee yang akan diupdate
        $employee = Employee::find($request->id);
        if (!$employee) {
            return response()->json([
                'message' => 'Employee tidak ditemukan'
            ], 404);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'company_id'        => 'required|exists:companies,id',
            'user_id'           => [
                'nullable',
                Rule::exists('users', 'id'),
                Rule::unique('employees', 'user_id')->ignore($employee->id),
            ],
            'employee_number'   => 'required|string|max:50|unique:employees,employee_number,' . $employee->id . ',id,company_id,' . $request->company_id,
            'name'              => 'required|string|max:100',
            'division_id'       => 'required|exists:divisions,id',
            'position_id'       => 'required|exists:positions,id',
            'join_date'         => 'nullable|date',
            'end_date'          => 'nullable|date|after_or_equal:join_date',
            'employment_status' => 'required|string|in:permanent,contract,intern,probation',
            'grade_level'       => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Pastikan user login
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            // Update data employee
            $employee->update($validator->validated());

            return response()->json([
                'message' => 'Employee berhasil diupdate',
                'data'    => $employee,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengupdate employee: ' . $e->getMessage(),
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
            $employee = Employee::findOrFail($id);
            $employee->load(['company','user','division','position']);
            return response()->json([
                'message' => 'Data ditemukan',
                'data' => $employee,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Data tidak ditemukan: ' . $e->getMessage()], 500);
        }
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:employees,id',
        ]);
        
        // Cek autentikasi user
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }
        
        try {
            $employee = Employee::findOrFail($validated['id']);
            $employee->delete();

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
