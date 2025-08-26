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
        'company_id', 'name', 'is_active','requester_position_id','approval_type'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function requesterPosition()
    {
        return $this->belongsTo(Position::class, 'requester_position_id', 'id');
    }

    public function steps()
    {
        return $this->hasMany(ApprovalStep::class, 'approval_flow_id');
    }

    public function amountFlows()
    {
        return $this->hasMany(ApprovalAmountFlow::class, 'approval_flow_id');
    }

    public function getApprovalSteps($requesterPositionId, $amount)
    {
        if ($this->approval_type === 'hierarchy') {
            return $this->steps()->where('requester_position_id', $requesterPositionId)->get();
        }

        if ($this->approval_type === 'amount') {
            $flow = $this->amountFlows()
                         ->where('min_amount', '<=', $amount)
                         ->where(function ($q) use ($amount) {
                             $q->where('max_amount', '>=', $amount)
                               ->orWhereNull('max_amount');
                         })
                         ->first();

            return $flow ? $flow->steps : collect([]);
        }

        return collect([]);
    }
}
