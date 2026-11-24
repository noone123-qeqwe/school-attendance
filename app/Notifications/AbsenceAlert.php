<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbsenceAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public $attendance;
    public $signedUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct($attendance, $signedUrl = null)
    {
        $this->attendance = $attendance;
        $this->signedUrl = $signedUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Support custom semaphore channel via App\Channels\SemaphoreChannel
        return ['mail', \App\Channels\SemaphoreChannel::class];
    }

    private function isStudentNotifiable(object $notifiable): bool
    {
        return ($notifiable->id ?? null) === $this->attendance->user_id
            || (($notifiable->role ?? null) === 'student');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $studentName = $this->attendance->user->name ?? 'Student';
        $status = $this->attendance->status ?? 'Absent';
        $formattedDate = $this->attendance->date ? \Illuminate\Support\Carbon::parse($this->attendance->date)->format('M d, Y') : 'Session';
        $isSelf = $this->isStudentNotifiable($notifiable);

        $mail = (new MailMessage)
            ->subject('Absence Alert: ' . $studentName)
            ->greeting('Hello ' . ($notifiable->name ?? ($isSelf ? $studentName : 'Parent/Guardian')) . ',')
            ->line('This is an automated notification from the School Attendance System.')
            ->line($isSelf
                ? 'You were marked **' . $status . '** in **' . $this->attendance->subject_code . '** on ' . $formattedDate . '.'
                : 'Your child, **' . $studentName . '**, was marked **' . $status . '** in **' . $this->attendance->subject_code . '** on ' . $formattedDate . '.');
            
        if ($this->signedUrl) {
            $mail->action('Submit Excuse Letter', $this->signedUrl);
        } else {
            $mail->action('View Attendance Record', url($isSelf ? '/home' : '/parent/dashboard'));
        }
            
        return $mail->line('Please contact the teacher or submit an excuse letter if necessary.');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSemaphore(object $notifiable): string
    {
        $studentName = $this->attendance->user->name ?? 'Student';
        $status = $this->attendance->status ?? 'Absent';
        $formattedDate = $this->attendance->date ? \Illuminate\Support\Carbon::parse($this->attendance->date)->format('M d, Y') : 'Session';
        $link = $this->signedUrl ? " Submit excuse: {$this->signedUrl}" : "";

        if ($this->isStudentNotifiable($notifiable)) {
            return "School Alert: You were marked {$status} in {$this->attendance->subject_code} on {$formattedDate}.{$link}";
        }

        return "School Alert: {$studentName} was marked {$status} in {$this->attendance->subject_code} on {$formattedDate}.{$link}";
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $studentName = $this->attendance->user->name ?? 'Student';
        $status = $this->attendance->status ?? 'Absent';

        return [
            'type' => 'absence_alert',
            'attendance_id' => $this->attendance->id,
            'student_name' => $studentName,
            'subject_code' => $this->attendance->subject_code,
            'date' => $this->attendance->date,
            'message' => $this->isStudentNotifiable($notifiable)
                ? "You were marked {$status} in {$this->attendance->subject_code} on {$this->attendance->date}."
                : "Your child, {$studentName}, was marked {$status} in {$this->attendance->subject_code} on {$this->attendance->date}.",
        ];
    }
}
