<?php

namespace App\Services\User\Model;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    // Fillable fields yang boleh diisi mass assignment
    protected $fillable = [
        'name',
    ];

    // Relasi ke User
    // public function users()
    // {
    //     return $this->hasMany(\App\Service\User\Model\User::class, 'role_id');
    // }
}
