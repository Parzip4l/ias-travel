<?php

namespace App\Services\User\Model;

use Illuminate\Database\Eloquent\Model;

class BudgetCategory extends Model
{
    // Fillable fields yang boleh diisi mass assignment
    protected $table = "budget_categories";
    protected $fillable = [
        'name',
    ];

    public function budgets()
    {
        return $this->hasMany(PositionBudget::class);
    }
}
