<?php

namespace App\Services\Sppd\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SppdHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'sppd_id', 'user_id', 'status_awal', 'status_akhir', 'catatan'
    ];

    public function sppd()
    {
        return $this->belongsTo(Sppd::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
