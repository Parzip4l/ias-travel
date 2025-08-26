<?php

namespace App\Services\Employee\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Model 
use App\Services\Company\Model\Company;
use App\Services\User\Model\Departement;
use App\Services\User\Model\Position;
use App\Models\User;

class Employee extends Model
{
    protected $table = "employees";

    protected $fillable = [
        'company_id',
        'user_id',  
        'employee_number',
        'name',
        'division_id',
        'position_id',
        'join_date',
        'end_date',
        'employment_status',
        'grade_level',
        
        'gender',
        'date_of_birth',
        'place_of_birth',
        'marital_status',
        'national_id',
        'tax_number',
        'phone_number',
        'address',
        'kontak_darurat',
    ];

    /**
     * Relasi ke Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relasi ke User (jika karyawan punya akun login)
     */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Division
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    /**
     * Relasi ke Position
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
