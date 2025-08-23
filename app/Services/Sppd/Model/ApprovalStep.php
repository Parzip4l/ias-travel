<?php

namespace App\Services\Sppd\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Services\Company\Model\Company;
use App\Services\User\Model\Departement;
use App\Services\User\Model\Position;
use App\Services\Sppd\Model\ApprovalFlow;
use App\Models\User;

class ApprovalStep extends Model
{
    use HasFactory;

    protected $table = 'approval_steps';
    protected $fillable = [
        'approval_flow_id', 'step_order', 'user_id', 'division_id', 'position_id', 'role','is_final'
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id', 'id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'division_id', 'id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id', 'id');
    }
    
}
