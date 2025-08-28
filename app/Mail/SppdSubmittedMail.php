<?php

namespace App\Mail;

use App\Services\Sppd\Model\Sppd;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class SppdSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $sppd;

    public function __construct(Sppd $sppd)
    {
        $this->sppd = $sppd;
    }

    public function build()
    {
        return $this->subject('Pengajuan SPPD Anda Berhasil')
            ->view('emails.sppd_submitted');
    }
}
