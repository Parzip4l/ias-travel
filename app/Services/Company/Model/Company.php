<?php

namespace App\Services\Company\Model;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    // Fillable fields yang boleh diisi mass assignment
    protected $table="companies";
    protected $fillable = [
        'name',
        'customer_id',
        'email',
        'image',
        'company_type_id',
        'phone',
        'address',
        'zipcode',
        'is_pkp',
        'npwp_number',
        'npwp_file',
        'sppkp_file',
        'skt_file',
        'environment',
        'is_active',
        'verified_at',
        'verification_note',
    ];

    public function companyType()
    {
        return $this->belongsTo(companyType::class);
    }
}
