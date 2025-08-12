<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends Notification
{
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = env('FRONTEND_URL', 'http://127.0.0.1:8001')
             . '/reset-password?token=' . $this->token
             . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Reset Password IAS Travel')
            ->view('emails.reset-pass', [
                'user' => $notifiable,
                'url'  => $url,
            ]);
    }
}
