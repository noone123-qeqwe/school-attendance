<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public string $purpose,
        public string $name
    ) {}

    public function envelope(): Envelope
    {
        if ($this->purpose === 'forgot_password') {
            $subject = 'Your Password Reset OTP — Smart Classroom System';
        } elseif ($this->purpose === 'register') {
            $subject = 'Your Registration OTP — Smart Classroom System';
        } elseif ($this->purpose === 'change_email') {
            $subject = 'Your Email Change OTP — Smart Classroom System';
        } else {
            $subject = 'Your Password Change OTP — Smart Classroom System';
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp');
    }
}
