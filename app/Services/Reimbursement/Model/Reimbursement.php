<?php

namespace App\Services\Reimbursement\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Services\Sppd\Model\Sppd;

class Reimbursement extends Model
{
    use HasFactory;

    protected $fillable = [
        'sppd_id',
        'category_id',
        'user_id',
        'title',
        'description',
        'amount',
        'status',
        'notes',
    ];

    public function sppd()
    {
        return $this->belongsTo(Sppd::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvals()
    {
        return $this->hasMany(ReimbursementApproval::class);
    }

    public function files()
    {
        return $this->hasMany(ReimbursementFile::class);
    }

    public function categories()
    {
        return $this->belongsTo(ReimburesementCategory::class);
    }
}
