<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventRescheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $event;
    public $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Event $event, string $reason)
    {
        $this->event = $event;
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $type = str_replace('_', ' ', $this->event->type);
        return [
            'title' => "Event Rescheduled: {$this->event->name}",
            'message' => "A {$type} you are attending has been rescheduled to {$this->event->date->format('M j')} at {$this->event->start_time->format('h:i A')}. Reason: {$this->reason}.",
            'icon' => 'bi-calendar-range',
            'link' => route('student.calendar') // Fallback link
        ];
    }
}
