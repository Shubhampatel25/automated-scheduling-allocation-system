<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $expiresInMinutes;

    public function __construct(string $otp, int $expiresInMinutes = 10)
    {
        $this->otp              = $otp;
        $this->expiresInMinutes = (string) $expiresInMinutes;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Password Reset OTP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
        );
    }
}
