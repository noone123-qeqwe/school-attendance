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

    public function __construct(
        public string $otp,
        public string $purpose,
        public string $name
    ) {}

    public function envelope(): Envelope
    {
        $appName = config('app.name', 'Smart Classroom Attendance System');
        return new Envelope(
            subject: "Your {$appName} Verification Code",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            text: 'emails.otp_plain',
        );
    }
}
