<?php

namespace App\Services\Sppd\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SppdApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'sppd_id', 'approver_id', 'role', 'status', 'catatan', 'approved_at'
    ];

    public function sppd()
    {
        return $this->belongsTo(Sppd::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
