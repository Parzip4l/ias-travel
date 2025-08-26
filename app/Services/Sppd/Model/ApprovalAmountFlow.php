<?php 

namespace App\Services\Sppd\Model;

use Illuminate\Database\Eloquent\Model;

class ApprovalAmountFlow extends Model
{
    protected $fillable = [
        'approval_flow_id', 'min_amount', 'max_amount'
    ];

    public function flow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id');
    }

    public function steps()
    {
        return $this->hasMany(ApprovalAmountStep::class, 'approval_amount_flow_id');
    }
}
