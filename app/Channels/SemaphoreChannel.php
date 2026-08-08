<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Services\SemaphoreService;

class SemaphoreChannel
{
    public function send($notifiable, Notification $notification)
    {
        // Check if notification has a toSemaphore method
        if (!method_exists($notification, 'toSemaphore')) {
            return;
        }

        $message = $notification->toSemaphore($notifiable);
        
        // Try to get phone from the notifiable model
        $phone = $notifiable->phone ?? $notifiable->guardian_phone ?? null;

        if ($phone && $message) {
            app(SemaphoreService::class)->send($phone, $message);
        }
    }
}
