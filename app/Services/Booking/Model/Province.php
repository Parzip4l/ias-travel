<?php

namespace App\Services\Booking\Model;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $table = 'provinces';
    public $timestamps = false;

    public function regencies()
    {
        return $this->hasMany(Regency::class, 'province_id', 'id');
    }
}
