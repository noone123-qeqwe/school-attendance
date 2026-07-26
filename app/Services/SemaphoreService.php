<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SemaphoreService
{
    protected string $apiKey;
    protected string $senderName;

    public function __construct()
    {
        $this->apiKey     = config('services.semaphore.api_key');
        $this->senderName = config('services.semaphore.sender_name', 'OsmenaAtt');
    }

    /**
     * Send an SMS via Semaphore API.
     */
    public function send(string $number, string $message): bool
    {
        // Normalize PH number: 09xxxxxxxxx → 639xxxxxxxxx
        $number = preg_replace('/\D/', '', $number);
        if (str_starts_with($number, '0')) {
            $number = '63' . substr($number, 1);
        }

        try {
            $response = Http::post('https://api.semaphore.co/api/v4/messages', [
                'apikey'      => $this->apiKey,
                'number'      => $number,
                'message'     => $message,
                'sendername'  => $this->senderName,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Semaphore SMS failed', ['response' => $response->body()]);
            return false;

        } catch (\Exception $e) {
            Log::error('Semaphore SMS exception', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
