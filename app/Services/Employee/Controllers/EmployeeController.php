<?php

namespace App\Services\Employee\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Vinkla\Hashids\Facades\Hashids;

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

// Export Impot
use App\Services\Employee\Exports\CompanyExport;
use App\Services\Employee\Imports\EmployeeImport;

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

            'gender'            => 'nullable|in:male,female,other',
            'date_of_birth'     => 'nullable|date|before:today',
            'place_of_birth'    => 'nullable|string|max:100',
            'marital_status'    => 'nullable|in:single,married,divorced,widowed',
            'national_id'       => 'nullable|digits:16', // KTP Indonesia = 16 digit
            'tax_number'        => 'nullable|numeric',
            'phone_number'      => 'nullable|numeric',
            'address'           => 'nullable|string|max:255',
            'kontak_darurat'    => 'nullable|string|max:255',
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

            'gender'            => 'nullable|in:male,female,other',
            'date_of_birth'     => 'nullable|date|before:today',
            'place_of_birth'    => 'nullable|string|max:100',
            'marital_status'    => 'nullable|in:single,married,divorced,widowed',
            'national_id'       => 'nullable|digits:16',
            'tax_number'        => 'nullable|numeric',
            'phone_number'      => 'nullable|numeric',
            'address'           => 'nullable|string|max:255',
            'kontak_darurat'    => 'nullable|string|max:255',
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


    public function findbyId($hash)
    {
        $id = Hashids::decode($hash);
        if (empty($id)) {
            return response()->json(['message' => 'Invalid ID.'], 400);
        }
        $id = $id[0];

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

    public function byUserid($id)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $employee = Employee::where('user_id',$id)->first();
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
            'id' => 'required|string',
        ]);
        
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        $id = dhid($validated['id']);
        if (!$id) {
            return response()->json([
                'message' => 'Invalid ID.'
            ], 400);
        }
        
        try {
            $employee = Employee::findOrFail($id);
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


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:2048',
        ]);

        try {
            Excel::import(new EmployeeImport, $request->file('file'));

            return response()->json([
                'message' => 'Data berhasil diimport',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal import: ' . $e->getMessage(),
            ], 500);
        }
    }
}
