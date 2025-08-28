<?php

namespace App\Mail;

use App\Services\Sppd\Model\Sppd;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SppdApprovalRequestMail extends Mailable
{
    public $sppd;
    public $approver;

    public function __construct($sppd, $approver)
    {
        $this->sppd = $sppd;
        $this->approver = $approver;
    }

    public function build()
    {
        return $this->view('emails.sppd_approval_request')
                    ->with([
                        'sppd' => $this->sppd,
                        'approver' => $this->approver,
                    ]);
    }
}
