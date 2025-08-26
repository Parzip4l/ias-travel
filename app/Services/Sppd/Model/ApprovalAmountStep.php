<?php

namespace App\Services\Sppd\Model;

use Illuminate\Database\Eloquent\Model;
use App\Services\User\Model\Departement;
use App\Services\User\Model\Position;

class ApprovalAmountStep extends Model
{
    protected $fillable = [
        'approval_amount_flow_id', 'step_order', 'user_id',
        'division_id', 'position_id', 'role', 'is_final'
    ];

    public function flow()
    {
        return $this->belongsTo(ApprovalAmountFlow::class, 'approval_amount_flow_id');
    }

    public function division()
    {
        return $this->belongsTo(Departement::class, 'division_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }
}
