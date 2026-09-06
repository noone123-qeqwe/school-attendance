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

    /**
     * The number of seconds before the job should timeout.
     */
    public $timeout = 30;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    public function __construct(
        public string $otp,
        public string $purpose,
        public string $name
    ) {}

    public function envelope(): Envelope
    {
        $appName = config('app.name', 'Smart Classroom Attendance System');
        $fromAddress = config('mail.from.address', 'osmenacolleges.attendance@gmail.com');
        $fromName = config('mail.from.name', $appName);

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($fromAddress, $fromName),
            replyTo: [
                new \Illuminate\Mail\Mailables\Address($fromAddress, $fromName),
            ],
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

    public function headers(): \Illuminate\Mail\Mailables\Headers
    {
        return new \Illuminate\Mail\Mailables\Headers(
            text: [
                'X-Priority' => '1',
                'X-MSMail-Priority' => 'High',
                'Importance' => 'High',
            ],
        );
    }
}
