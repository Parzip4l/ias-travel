<?php

namespace App\Services\User\Model;

use Illuminate\Database\Eloquent\Model;

class PositionsBudget extends Model
{
    // Fillable fields yang boleh diisi mass assignment
    protected $table = "positions_budgets";
    protected $fillable = [
        'position_id',
        'category_id',
        'type',
        'max_budget'
    ];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function category()
    {
        return $this->belongsTo(BudgetCategory::class);
    }
}
