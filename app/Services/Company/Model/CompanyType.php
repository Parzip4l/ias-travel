<?php

namespace App\Services\Company\Model;

use Illuminate\Database\Eloquent\Model;

class CompanyType extends Model
{
    // Fillable fields yang boleh diisi mass assignment
    protected $table="company_types";
    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];
}
