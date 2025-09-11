<?php

namespace App\Services\Reimbursement\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'reimbursement_id',
        'approved_by',
        'status',
        'notes',
    ];

    public function reimbursement()
    {
        return $this->belongsTo(Reimbursement::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
