<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $event;

    /**
     * Create a new notification instance.
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // and mail if SMTP was configured
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "New Meeting Invitation: {$this->event->name}",
            'message' => "You have been invited to a meeting on {$this->event->date->format('M j, Y')} at {$this->event->start_time->format('h:i A')} by {$this->event->organizer->name}.",
            'icon' => 'bi-envelope-paper-heart',
            'link' => route('student.calendar')
        ];
    }
}
