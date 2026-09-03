<?php

namespace App\Jobs;

use App\Services\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWebPushJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [5, 30, 60];

    public function __construct(
        public string $targetType,
        public mixed $targetId,
        public string $title,
        public string $body,
        public array $options = []
    ) {}

    /**
     * Execute the job on the queue worker.
     */
    public function handle(WebPushService $service): void
    {
        try {
            match ($this->targetType) {
                'user' => $service->sendToUser($this->targetId, $this->title, $this->body, $this->options),
                'parents' => $service->sendToParentsOfStudent($this->targetId, $this->title, $this->body, $this->options),
                'role' => $service->sendToRole((string) $this->targetId, $this->title, $this->body, $this->options),
                'broadcast' => $service->broadcastAnnouncement($this->title, $this->body, $this->options, $this->targetId ?: null),
                default => Log::warning("SendWebPushJob: Unknown target type '{$this->targetType}'"),
            };
        } catch (\Throwable $e) {
            Log::error("SendWebPushJob failed for target [{$this->targetType}:{$this->targetId}]: " . $e->getMessage());
            throw $e;
        }
    }
}
