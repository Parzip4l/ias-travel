<?php

namespace App\Services\Sppd\Model;

use Illuminate\Database\Eloquent\Model;

class SppdWilayah extends Model
{
    protected $table = 'sppd_wilayah';

    protected $fillable = [
        'sppd_id',
        'province_id',
        'regency_id',
        'district_id',
        'village_id',
        'full_address',
        'latitude',
        'longitude',
    ];

    /**
     * Relasi ke tabel sppd
     */
    public function sppd()
    {
        return $this->belongsTo(Sppd::class, 'sppd_id');
    }

    public function province()
    {
        return $this->belongsTo(\App\Services\Booking\Model\Province::class, 'province_id');
    }

    public function regency()
    {
        return $this->belongsTo(\App\Services\Booking\Model\Regency::class, 'regency_id');
    }

    public function district()
    {
        return $this->belongsTo(\App\Services\Booking\Model\District::class, 'district_id');
    }

    public function village()
    {
        return $this->belongsTo(\App\Services\Booking\Model\Village::class, 'village_id');
    }
}
