<?php

namespace App\Services\Sppd\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SppdExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'sppd_id', 'kategori', 'deskripsi', 'jumlah', 'bukti_file'
    ];

    public function sppd()
    {
        return $this->belongsTo(Sppd::class);
    }
}
