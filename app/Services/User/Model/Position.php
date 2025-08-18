<?php

namespace App\Services\User\Model;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    // Fillable fields yang boleh diisi mass assignment
    protected $table = "positions";
    protected $fillable = [
        'name',
    ];

     public function budgets()
    {
        return $this->hasMany(PositionsBudget::class);
    }
}
