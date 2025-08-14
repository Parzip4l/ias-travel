<?php

namespace App\Services\Sppd\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SppdFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'sppd_id', 'jenis_file', 'file_path', 'uploaded_by', 'uploaded_at'
    ];

    public function sppd()
    {
        return $this->belongsTo(Sppd::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
