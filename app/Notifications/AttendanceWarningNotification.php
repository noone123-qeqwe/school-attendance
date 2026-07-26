<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Warning;

class AttendanceWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $warning;

    /**
     * Create a new notification instance.
     */
    public function __construct(Warning $warning)
    {
        $this->warning = $warning;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Urgent: Attendance Warning')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have received an attendance warning regarding your classes.')
            ->line('Subject/Class: ' . $this->warning->subject_code)
            ->line('Message: ' . $this->warning->message)
            ->line('Please review your attendance records and submit an excuse if applicable.')
            ->action('View Dashboard', url('/home'))
            ->line('If you have any questions, please contact your instructor or the administration.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'warning_id' => $this->warning->id,
            'subject_code' => $this->warning->subject_code,
            'message' => $this->warning->message,
        ];
    }
}
