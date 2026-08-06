<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParentDailyDigest extends Mailable
{
    use Queueable, SerializesModels;

    public $parentUser;
    public $digestData;

    public function __construct($parentUser, $digestData)
    {
        $this->parentUser = $parentUser;
        $this->digestData = $digestData;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Daily Attendance Digest',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.parent-digest',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
