<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbsenceAlert extends Notification
{
    use Queueable;

    public $attendance;

    /**
     * Create a new notification instance.
     */
    public function __construct($attendance)
    {
        $this->attendance = $attendance;
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
            ->subject('Absence Alert: ' . $this->attendance->user->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('This is an automated notification from the School Attendance System.')
            ->line('Your child, **' . $this->attendance->user->name . '**, was marked **Absent** in **' . $this->attendance->subject_code . '** on ' . $this->attendance->date->format('M d, Y') . '.')
            ->action('View Attendance Record', url('/parent/dashboard'))
            ->line('Please contact the teacher or submit an excuse letter if necessary.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'absence_alert',
            'attendance_id' => $this->attendance->id,
            'student_name' => $this->attendance->user->name,
            'subject_code' => $this->attendance->subject_code,
            'date' => $this->attendance->date,
            'message' => "Your child, {$this->attendance->user->name}, was marked Absent in {$this->attendance->subject_code} on {$this->attendance->date}.",
        ];
    }
}
