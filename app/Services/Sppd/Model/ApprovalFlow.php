<?php

namespace App\Services\Sppd\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Services\Company\Model\Company;
use App\Services\User\Model\Position;

class ApprovalFlow extends Model
{
    use HasFactory;

    protected $table = 'approval_flows';
    protected $fillable = [
        'company_id', 'name', 'is_active','requester_position_id'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function requesterPosition()
    {
        return $this->belongsTo(Position::class, 'requester_position_id', 'id');
    }
}
