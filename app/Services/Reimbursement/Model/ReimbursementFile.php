<?php

namespace App\Services\Reimbursement\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'reimbursement_id',
        'file_path',
        'file_type',
        'uploaded_by',
    ];

    public function reimbursement()
    {
        return $this->belongsTo(Reimbursement::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}