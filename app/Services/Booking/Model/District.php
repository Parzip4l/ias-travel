<?php

namespace App\Services\Booking\Model;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = 'districts';
    public $timestamps = false;

    public function regency()
    {
        return $this->belongsTo(Regency::class, 'regency_id', 'id');
    }

    public function villages()
    {
        return $this->hasMany(Village::class, 'district_id', 'id');
    }
}
