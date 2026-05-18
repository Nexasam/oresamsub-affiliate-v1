<?php

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class AffiliateVerifyEmail extends VerifyEmail
{
    protected $affiliate;

    public function __construct($affiliate)
    {
        $this->affiliate = $affiliate;
    }

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify your email for ' . $this->affiliate->name)
            ->greeting('Hello from ' . $this->affiliate->name)
            ->line('Please verify your email to continue.')
            ->action('Verify Email', $verificationUrl)
            ->line('Thank you for choosing ' . $this->affiliate->name);
    }
}