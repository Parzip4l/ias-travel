<?php

namespace App\Services\Booking\Model;

use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    protected $table = 'villages';
    public $timestamps = false;

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'id');
    }
}
