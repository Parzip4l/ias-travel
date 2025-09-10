<?php

namespace App\Services\Sppd\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Services\Payment\Model\Payment;

class Sppd extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_sppd', 'user_id', 'tujuan', 'lokasi_tujuan', 
        'tanggal_berangkat', 'tanggal_pulang', 'transportasi', 
        'biaya_estimasi', 'biaya_realisasi', 'status','keperluan'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approvals()
    {
        return $this->hasMany(SppdApproval::class);
    }

    public function files()
    {
        return $this->hasMany(SppdFile::class);
    }

    public function histories()
    {
        return $this->hasMany(SppdHistory::class);
    }

    public function expenses()
    {
        return $this->hasMany(SppdExpense::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'sppd_id');
    }

    public function wilayah()
    {
        return $this->hasOne(SppdWilayah::class, 'sppd_id');
    }
}
