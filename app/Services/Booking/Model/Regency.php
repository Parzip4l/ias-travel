<?php

namespace App\Services\Booking\Model;

use Illuminate\Database\Eloquent\Model;

class Regency extends Model
{
    protected $table = 'regencies';
    public $timestamps = false;

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'id');
    }

    public function districts()
    {
        return $this->hasMany(District::class, 'regency_id', 'id');
    }
}
