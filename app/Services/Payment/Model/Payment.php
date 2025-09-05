<?php

namespace App\Services\Payment\Model;

use Illuminate\Database\Eloquent\Model;
use App\Services\Sppd\Model\Sppd;

class Payment extends Model
{
    // Status sesuai Xendit Invoice API
    const STATUS_PENDING   = 'PENDING';
    const STATUS_PAID      = 'PAID';
    const STATUS_EXPIRED   = 'EXPIRED';
    const STATUS_FAILED    = 'FAILED';

    protected $fillable = [
        'sppd_id',
        'external_id',
        'invoice_id',
        'amount',
        'status',
        'payer_email',
        'invoice_url',
        'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'amount'       => 'integer',
    ];

    /*
     * Relations
     */
    public function sppd()
    {
        return $this->belongsTo(Sppd::class, 'sppd_id');
    }

    /*
     * Helpers
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
