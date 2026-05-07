<?php

namespace App\Services\User\Model;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'module',
        'action',
    ];
}
