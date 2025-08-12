<?php

namespace App\Services\User\Model;

use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    // Fillable fields yang boleh diisi mass assignment
    protected $table = "divisions";
    protected $fillable = [
        'name',
        'head_id',
    ];

    public function head()
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    // Relasi ke User
    // public function users()
    // {
    //     return $this->hasMany(\App\Service\User\Model\User::class, 'role_id');
    // }
}
