<?php

namespace App\Services;

use App\Rules\ValidPhoneNumber;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SemaphoreService
{
    private const ENDPOINT = 'https://api.semaphore.co/api/v4/messages';

    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly ?string $senderName = null,
        private readonly ?bool $enabled = null,
    ) {}

    /**
     * Send an SMS, returning true only when Semaphore confirms network delivery.
     * Queued or pending responses are not reported as successful delivery.
     */
    public function send(string $number, string $message): bool
    {
        if (!app(FeatureFlagService::class)->enabled('feature.sms_notifications')) {
            Log::warning('Semaphore SMS notifications are disabled by the feature settings; message was not submitted.');

            return false;
        }

        $enabled = $this->smsEnabled();
        if (!$enabled) {
            Log::warning('Semaphore SMS is disabled; message was not submitted.');

            return false;
        }

        $apiKey = $this->configuredApiKey();
        if (!$this->isUsableApiKey($apiKey)) {
            Log::warning('Semaphore SMS is enabled without a usable API key; message was not submitted.');

            return false;
        }

        $normalizedNumber = ValidPhoneNumber::normalize($number);
        if ($normalizedNumber === null) {
            Log::warning('Semaphore SMS was not submitted because the recipient number is invalid.');

            return false;
        }

        if (trim($message) === '') {
            Log::warning('Semaphore SMS was not submitted because the message is empty.');

            return false;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(3)
                ->timeout(8)
                ->post(self::ENDPOINT, [
                    'apikey' => $apiKey,
                    'number' => $normalizedNumber,
                    'message' => $message,
                    'sendername' => trim((string) ($this->senderName ?? config('services.semaphore.sender_name', ''))),
                ]);
        } catch (Throwable) {
            // Do not log exception text: HTTP client exceptions may contain request details.
            Log::warning('Semaphore SMS provider request failed before a response was received.', [
                'failure_category' => 'connection_or_timeout',
            ]);

            return false;
        }

        if (!$response->successful()) {
            Log::warning('Semaphore SMS provider rejected the request.', [
                'failure_category' => $this->failureCategory($response),
                'http_status' => $response->status(),
            ]);

            return false;
        }

        $messages = $response->json();
        if (!is_array($messages) || !array_is_list($messages) || $messages === []) {
            Log::warning('Semaphore SMS returned an unexpected response.', [
                'failure_category' => 'invalid_provider_response',
            ]);

            return false;
        }

        $statuses = array_map(
            static fn (mixed $item): string => is_array($item) ? strtolower(trim((string) ($item['status'] ?? ''))) : '',
            $messages,
        );

        if (count($statuses) !== 1 || $statuses[0] !== 'sent') {
            $category = in_array('failed', $statuses, true) ? 'provider_delivery_failed' : 'delivery_unconfirmed';
            Log::notice('Semaphore SMS was accepted or processed without confirmed network delivery.', [
                'failure_category' => $category,
            ]);

            return false;
        }

        return true;
    }

    public function isConfigured(): bool
    {
        return app(FeatureFlagService::class)->enabled('feature.sms_notifications')
            && $this->smsEnabled()
            && $this->isUsableApiKey($this->configuredApiKey());
    }

    private function smsEnabled(): bool
    {
        return $this->enabled ?? (bool) config('services.semaphore.enabled', false);
    }

    private function configuredApiKey(): string
    {
        return trim((string) ($this->apiKey ?? config('services.semaphore.api_key')));
    }

    private function isUsableApiKey(string $apiKey): bool
    {
        return $apiKey !== ''
            && !preg_match('/^(?:your[_-]|replace|change[_-]?me|placeholder|dummy|test)/i', $apiKey);
    }

    private function failureCategory(Response $response): string
    {
        if ($response->status() === 429) {
            return 'rate_limited';
        }

        $payload = $response->json();
        $providerText = '';
        if (is_array($payload)) {
            foreach (['error', 'message', 'status', 'code'] as $field) {
                if (isset($payload[$field]) && is_scalar($payload[$field])) {
                    $providerText .= ' ' . strtolower((string) $payload[$field]);
                }
            }
        }

        if (str_contains($providerText, 'credit') || str_contains($providerText, 'balance')) {
            return 'insufficient_credits';
        }

        if (str_contains($providerText, 'rate') || str_contains($providerText, 'limit')) {
            return 'rate_limited';
        }

        if (str_contains($providerText, 'number') || str_contains($providerText, 'recipient')) {
            return 'invalid_recipient';
        }

        if (str_contains($providerText, 'key') || str_contains($providerText, 'unauthorized')) {
            return 'invalid_provider_configuration';
        }

        if ($response->serverError()) {
            return 'provider_unavailable';
        }

        return 'provider_rejected';
    }
}
