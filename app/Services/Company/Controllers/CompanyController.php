<?php

namespace App\Services\Company\Controllers;

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

class CompanyController extends Controller
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

        $data = Company::all();
        $data->load(['companyType']);
        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:50|unique:companies,name',
            'customer_id'      => 'required|string|unique:companies,customer_id',
            'email'            => 'required|email|unique:companies,email',
            'image'            => 'nullable|file|mimes:jpg,jpeg,png|max:1024',
            'company_type_id'  => 'required|exists:company_types,id',
            'phone'            => 'required|string|max:20',
            'address'          => 'required|string',
            'zipcode'          => 'nullable|string|max:10',
            'is_pkp'           => 'required|boolean',
            'npwp_number'      => 'nullable|string|max:25',
            'npwp_file'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:1024',
            'sppkp_file'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:1024',
            'skt_file'         => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:1024',
            'environment'      => 'nullable|string',
            'is_active'        => 'boolean',
            'verified_at'      => 'nullable|date',
            'verification_note'=> 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            $data = $validator->validated();

            // Upload file jika ada
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('uploads/logo', 'public');
            }
            if ($request->hasFile('npwp_file')) {
                $data['npwp_file'] = $request->file('npwp_file')->store('uploads/npwp', 'public');
            }
            if ($request->hasFile('sppkp_file')) {
                $data['sppkp_file'] = $request->file('sppkp_file')->store('uploads/sppkp', 'public');
            }
            if ($request->hasFile('skt_file')) {
                $data['skt_file'] = $request->file('skt_file')->store('uploads/skt', 'public');
            }

            // Simpan company baru
            $company = Company::create($data);

            return response()->json([
                'message' => 'Company berhasil dibuat',
                'data' => $company,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan company: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name'             => [
                'required', 'string', 'max:50',
                Rule::unique('companies', 'name')->ignore($request->id)
            ],
            'customer_id'      => [
                'required', 'string',
                Rule::unique('companies', 'customer_id')->ignore($request->id)
            ],
            'email'            => [
                'required', 'email',
                Rule::unique('companies', 'email')->ignore($request->id)
            ],
            'image'            => 'nullable|file|mimes:jpg,jpeg,png|max:1024',
            'company_type_id'  => 'required|exists:company_types,id',
            'phone'            => 'required|string|max:20',
            'address'          => 'required|string',
            'zipcode'          => 'nullable|string|max:10',
            'is_pkp'           => 'required|boolean',
            'npwp_number'      => 'nullable|string|max:25',
            'npwp_file'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:1024',
            'sppkp_file'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:1024',
            'skt_file'         => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:1024',
            'environment'      => 'nullable|string',
            'is_active'        => 'boolean',
            'verified_at'      => 'nullable|date',
            'verification_note'=> 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            $company = Company::findOrFail($request->id);
            $data = $validator->validated();

            // Upload file jika ada (replace yang lama)
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('uploads/logo', 'public');
            }
            if ($request->hasFile('npwp_file')) {
                $data['npwp_file'] = $request->file('npwp_file')->store('uploads/npwp', 'public');
            }
            if ($request->hasFile('sppkp_file')) {
                $data['sppkp_file'] = $request->file('sppkp_file')->store('uploads/sppkp', 'public');
            }
            if ($request->hasFile('skt_file')) {
                $data['skt_file'] = $request->file('skt_file')->store('uploads/skt', 'public');
            }

            // Update data
            $company->update($data);

            return response()->json([
                'message' => 'Company berhasil diperbarui',
                'data'    => $company,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui company: ' . $e->getMessage(),
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
            $company = Company::findOrFail($id);
            $company->load(['companyType']);
            return response()->json([
                'message' => 'Data ditemukan',
                'data' => $company,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Data tidak ditemukan: ' . $e->getMessage()], 500);
        }
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:companies,id',
        ]);
        
        // Cek autentikasi user
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        try {
            $company = Company::findOrFail($validated['id']);
            $company->delete();

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
