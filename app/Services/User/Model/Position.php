<?php

namespace App\Services\User\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Services\Company\Model\Company;

class Position extends Model
{
    // Fillable fields yang boleh diisi mass assignment
    protected $table = "positions";
    protected $fillable = [
        'name',
        'company_id'
    ];

    public function budgets()
    {
        return $this->hasMany(PositionsBudget::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
