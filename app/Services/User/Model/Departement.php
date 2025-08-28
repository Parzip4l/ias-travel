<?php

namespace App\Services\User\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Services\Company\Model\Company;

class Departement extends Model
{
    // Fillable fields yang boleh diisi mass assignment
    protected $table = "divisions";
    protected $fillable = [
        'name',
        'head_id',
        'company_id'
    ];

    public function head()
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Relasi ke User
    // public function users()
    // {
    //     return $this->hasMany(\App\Service\User\Model\User::class, 'role_id');
    // }
}
