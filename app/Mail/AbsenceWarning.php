<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbsenceWarning extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $subject;
    public $absencesCount;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $subject, $absencesCount)
    {
        $this->user = $user;
        $this->subject = $subject;
        $this->absencesCount = $absencesCount;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $cc = [];
        if (!empty($this->user->guardian_email)) {
            $cc[] = new \Illuminate\Mail\Mailables\Address(
                $this->user->guardian_email,
                'Guardian of ' . $this->user->name
            );
        }

        return new Envelope(
            subject: '⚠️ Attendance Warning: ' . $this->subject->name,
            cc: $cc,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.absence_warning',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
