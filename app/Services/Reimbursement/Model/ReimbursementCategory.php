<?php

namespace App\Services\Reimbursement\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Services\Sppd\Model\Sppd;
use App\Services\Company\Model\Company;

class ReimbursementCategory extends Model
{
    use HasFactory;
    protected $table="reimbursement_category";
    protected $fillable = [
        'name',
        'code',
        'company_id',
    ];

    public function companies()
    {
        return $this->belongsTo(Company::class,'company_id');
    }
}
