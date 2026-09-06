<?php

namespace App\Services\Email;

use App\Mail\OtpMail;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailDeliveryService
{
    /**
     * Get the descriptive identifier of the active email provider / transport.
     */
    public function getActiveProviderName(): string
    {
        $defaultMailer = config('mail.default', 'log');
        $mailerConfig = config("mail.mailers.{$defaultMailer}", []);
        $transport = $mailerConfig['transport'] ?? $defaultMailer;

        if ($transport === 'smtp') {
            $host = $mailerConfig['host'] ?? '127.0.0.1';
            $port = $mailerConfig['port'] ?? 587;
            $encryption = $mailerConfig['encryption'] ?? 'tls';
            return "smtp ({$host}:{$port}, {$encryption})";
        }

        return (string) $transport;
    }

    /**
     * Determine whether the active mailer is capable of real outbound delivery.
     */
    public function isOutboundDeliveryConfigured(): bool
    {
        // Unit tests using Mail::fake() or array transport are always permitted
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return true;
        }

        // Outbound HTTP API keys (Resend / Brevo) work on any cloud environment without SMTP restrictions
        if (!empty(config('services.resend.key')) || !empty(env('RESEND_API_KEY')) || !empty(env('BREVO_API_KEY')) || !empty(config('services.brevo.key'))) {
            return true;
        }

        $defaultMailer = config('mail.default', 'log');
        $mailerConfig = config("mail.mailers.{$defaultMailer}", []);
        $transport = $mailerConfig['transport'] ?? $defaultMailer;

        // Disallow log and array drivers for real OTP verification flows
        if (in_array($transport, ['log', 'array', 'null'], true)) {
            return false;
        }

        // For SMTP, ensure host, username, and password are not empty placeholders
        if ($transport === 'smtp') {
            $host = $mailerConfig['host'] ?? '';
            $user = $mailerConfig['username'] ?? '';
            $pass = $mailerConfig['password'] ?? '';

            if (empty($host) || $host === '127.0.0.1' || empty($user) || empty($pass)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deliver an OTP verification email through the configured email provider.
     */
    public function sendOtp(
        string $recipientEmail,
        string $otpCode,
        string $purpose,
        string $recipientName = 'User',
        ?string $requestId = null
    ): EmailDeliveryResult {
        $providerName = $this->getActiveProviderName();

        // Log the recipient for transparency — ensures no silent redirection
        Log::info("EmailDeliveryService: Sending OTP [purpose: {$purpose}, recipient: {$recipientEmail}, provider: {$providerName}]");

        // 1. Check HTTP API providers first (works over HTTPS port 443; never blocked on cloud/Render free tier)
        $resendKey = config('services.resend.key') ?: env('RESEND_API_KEY');

        if (!empty($resendKey) && !app()->runningUnitTests()) {
            $httpRes = $this->sendViaResendHttp($recipientEmail, $otpCode, $purpose, $recipientName, $resendKey, $requestId);
            if ($httpRes->success) {
                return $httpRes;
            }
            Log::warning("Resend HTTP delivery failed, falling back to standard mailer: {$httpRes->error}");
        }

        $brevoKey = config('services.brevo.key') ?: env('BREVO_API_KEY');
        if (!empty($brevoKey) && !app()->runningUnitTests()) {
            $httpRes = $this->sendViaBrevoHttp($recipientEmail, $otpCode, $purpose, $recipientName, $brevoKey, $requestId);
            if ($httpRes->success) {
                return $httpRes;
            }
            Log::warning("Brevo HTTP delivery failed, falling back to standard mailer: {$httpRes->error}");
        }

        // 2. Guard against unconfigured or dummy email drivers (prevents false success)
        if (!$this->isOutboundDeliveryConfigured()) {
            $errorMsg = 'Outbound email delivery is not configured. Please configure valid SMTP or API credentials in your environment.';
            Log::error("Email delivery aborted [provider: {$providerName}]: {$errorMsg}");
            return EmailDeliveryResult::rejected($providerName, $errorMsg, 500);
        }

        // 3. Transmit email via Laravel Mailer
        try {
            $mailable = new OtpMail($otpCode, $purpose, $recipientName);
            
            // In local/development environments, always send synchronously
            // In production with active queue workers, use queue for better performance
            $shouldQueue = config('queue.default') !== 'sync' 
                && !app()->environment('local', 'development') 
                && config('mail.queue', false);
            
            if ($shouldQueue) {
                Mail::to($recipientEmail)->queue($mailable);
                $messageId = 'queued_' . time();
                $responseText = 'QUEUED';
            } else {
                // Send synchronously to ensure immediate delivery
                // Note: Mail::send() returns void in Laravel 11+, message ID extraction happens via sent event
                Mail::to($recipientEmail)->send($mailable);
                $messageId = 'sent_' . time();
                $responseText = 'SENT';
            }

            return EmailDeliveryResult::accepted(
                provider: $providerName,
                messageId: $messageId,
                response: $responseText,
                diagnostics: [
                    'mailer'     => config('mail.default'),
                    'request_id' => $requestId,
                    'timestamp'  => now()->toIso8601String(),
                    'queued'     => $shouldQueue,
                    'environment' => app()->environment(),
                ]
            );
        } catch (Throwable $e) {
            $primaryError = $this->sanitizeErrorMessage($e->getMessage());
            Log::warning("Primary email delivery attempt failed [provider: {$providerName}]: {$primaryError}. Attempting SSL fallback on port 465...");

            // Automatic Port 465 SSL fallback if primary SMTP (port 587) timed out or encountered connection issues
            if (config('mail.default') === 'smtp' && !app()->runningUnitTests()) {
                try {
                    $mailable = new OtpMail($otpCode, $purpose, $recipientName);
                    Mail::mailer('smtp_ssl')->to($recipientEmail)->send($mailable);

                    $messageId = 'sent_ssl_' . time();
                    $debugResponse = 'SENT via SSL fallback';

                    Log::info("Email delivery succeeded via SSL fallback on port 465");
                    return EmailDeliveryResult::accepted(
                        provider: 'smtp (smtp.gmail.com:465, ssl)',
                        messageId: $messageId,
                        response: $debugResponse,
                        diagnostics: [
                            'mailer'     => 'smtp_ssl',
                            'fallback'   => 'port_465_ssl',
                            'request_id' => $requestId,
                            'timestamp'  => now()->toIso8601String(),
                        ]
                    );
                } catch (Throwable $fallbackErr) {
                    $fallbackError = $this->sanitizeErrorMessage($fallbackErr->getMessage());
                    Log::error("SSL fallback on port 465 also failed: {$fallbackError}");
                }
            }

            // Cloud provider diagnostic alert
            $isCloud = env('RENDER') || env('RENDER_SERVICE_ID') || env('RAILWAY_ENVIRONMENT');
            if ($isCloud && (str_contains($primaryError, 'timed out') || str_contains($primaryError, 'Connection refused') || str_contains($primaryError, '110'))) {
                Log::error("Cloud host blocked SMTP traffic. Note: Render Free Tier blocks outbound SMTP traffic on ports 25, 465, and 587. Configure an HTTP API key (e.g. RESEND_API_KEY or BREVO_API_KEY) or upgrade to a Render paid instance.");
            }

            Log::error("Email delivery failed [provider: {$providerName}]: {$primaryError}", [
                'exception_class' => get_class($e),
                'code'            => $e->getCode(),
            ]);

            return EmailDeliveryResult::rejected(
                provider: $providerName,
                error: 'Unable to send verification code. Please try again.',
                statusCode: 500,
                diagnostics: [
                    'internal_error' => $primaryError,
                    'exception_type' => get_class($e),
                ]
            );
        }
    }

    /**
     * Send an independent diagnostic test email (Requirement 19).
     * Bypasses OTP generation to isolate mail delivery configuration issues.
     */
    public function sendDiagnosticTestEmail(string $recipientEmail): EmailDeliveryResult
    {
        $providerName = $this->getActiveProviderName();

        try {
            $appName = config('app.name', 'Smart Classroom Attendance System');
            $timestamp = now()->toIso8601String();

            $sendCallback = function ($message) use ($recipientEmail, $appName) {
                $message->to($recipientEmail)
                    ->subject("{$appName} - Diagnostic Delivery Test (" . date('H:i:s') . ")");
            };

            Mail::raw(
                "Hello,\n\nThis is an automated diagnostic test email from {$appName}.\n\n" .
                "Diagnostic Details:\n" .
                "- Sent: {$timestamp}\n" .
                "- Active Provider: {$providerName}\n" .
                "- Server Time: " . now()->format('Y-m-d H:i:s T') . "\n\n" .
                "If you are reading this in Gmail, your email delivery pipeline is working correctly.\n",
                $sendCallback
            );

            $messageId = 'diagnostic_' . time();
            $debugResponse = 'SENT';

            return EmailDeliveryResult::accepted(
                provider: $providerName,
                messageId: $messageId,
                response: $debugResponse,
                diagnostics: [
                    'mailer'    => config('mail.default'),
                    'recipient' => $recipientEmail,
                    'timestamp' => $timestamp,
                ]
            );
        } catch (Throwable $e) {
            if (config('mail.default') === 'smtp' && !app()->runningUnitTests()) {
                try {
                    $appName = config('app.name', 'Smart Classroom Attendance System');
                    $sendCallback = function ($message) use ($recipientEmail, $appName) {
                        $message->to($recipientEmail)
                            ->subject("{$appName} - Diagnostic Delivery Test (" . date('H:i:s') . ")");
                    };
                    Mail::mailer('smtp_ssl')->raw(
                        "Hello,\n\nThis is an automated diagnostic test email from {$appName} (via SSL fallback on port 465).\n\n" .
                        "If you are reading this in Gmail, your email delivery pipeline is working correctly.\n",
                        $sendCallback
                    );

                    $messageId = 'diagnostic_ssl_' . time();
                    $debugResponse = 'SENT via SSL fallback';

                    return EmailDeliveryResult::accepted(
                        provider: 'smtp (smtp.gmail.com:465, ssl)',
                        messageId: $messageId,
                        response: $debugResponse,
                        diagnostics: [
                            'mailer'    => 'smtp_ssl',
                            'fallback'  => 'port_465_ssl',
                            'recipient' => $recipientEmail,
                            'timestamp' => now()->toIso8601String(),
                        ]
                    );
                } catch (Throwable $e2) {}
            }

            $maskedError = $this->sanitizeErrorMessage($e->getMessage());
            return EmailDeliveryResult::rejected(
                provider: $providerName,
                error: $maskedError,
                statusCode: 500,
                diagnostics: ['exception_class' => get_class($e)]
            );
        }
    }

    /**
     * Safely extract the provider message ID from a SentMessage.
     */
    protected function extractMessageId(?SentMessage $sentMessage): ?string
    {
        if (!$sentMessage) {
            return null;
        }

        try {
            $symfonySent = $sentMessage->getSymfonySentMessage();
            if ($symfonySent && method_exists($symfonySent, 'getMessageId')) {
                return $symfonySent->getMessageId();
            }
        } catch (Throwable) {
            // Non-critical fallback
        }

        try {
            return $sentMessage->getMessageId();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Safely extract debug output (e.g. SMTP 250 response) without leaking passwords.
     */
    protected function extractDebugResponse(?SentMessage $sentMessage): ?string
    {
        if (!$sentMessage) {
            return null;
        }

        try {
            $symfonySent = $sentMessage->getSymfonySentMessage();
            if ($symfonySent && method_exists($symfonySent, 'getDebug')) {
                $debug = (string) $symfonySent->getDebug();
                // Strip any potential auth lines for security
                $lines = explode("\n", $debug);
                $filtered = array_filter($lines, function ($line) {
                    return !str_contains($line, 'AUTH') && !str_contains($line, '334');
                });
                return trim(implode("\n", array_slice($filtered, -5)));
            }
        } catch (Throwable) {
            // Non-critical fallback
        }

        return 'ACCEPTED';
    }

    /**
     * Sanitize error messages to prevent secret disclosure in logs or responses.
     */
    protected function sanitizeErrorMessage(string $message): string
    {
        // Replace potential password/token patterns
        $sanitized = preg_replace('/password=[^\s&]+/i', 'password=***', $message);
        $sanitized = preg_replace('/key=[^\s&]+/i', 'key=***', $sanitized ?? $message);
        return $sanitized ?? $message;
    }

    /**
     * Deliver OTP via Resend HTTP API (HTTPS port 443; never blocked on cloud free tiers).
     */
    protected function sendViaResendHttp(
        string $recipientEmail,
        string $otpCode,
        string $purpose,
        string $recipientName,
        string $apiKey,
        ?string $requestId
    ): EmailDeliveryResult {
        try {
            $mailable = new OtpMail($otpCode, $purpose, $recipientName);
            $appName = config('app.name', 'Smart Classroom Attendance System');
            $fromAddress = env('RESEND_FROM', 'onboarding@resend.dev');

            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->post('https://api.resend.com/emails', [
                    'from'    => "{$appName} <{$fromAddress}>",
                    'to'      => [$recipientEmail],
                    'subject' => "Your {$appName} Verification Code",
                    'html'    => $mailable->render(),
                ]);

            if ($response->successful()) {
                $json = $response->json();
                return EmailDeliveryResult::accepted(
                    provider: 'resend (http api, port 443)',
                    messageId: $json['id'] ?? null,
                    response: 'HTTP 200 OK',
                    diagnostics: [
                        'transport'  => 'resend_http',
                        'request_id' => $requestId,
                        'timestamp'  => now()->toIso8601String(),
                    ]
                );
            }

            return EmailDeliveryResult::rejected(
                provider: 'resend (http api, port 443)',
                error: $response->body() ?: 'Resend API returned an error',
                statusCode: $response->status()
            );
        } catch (Throwable $e) {
            return EmailDeliveryResult::rejected(
                provider: 'resend (http api, port 443)',
                error: $this->sanitizeErrorMessage($e->getMessage()),
                statusCode: 500
            );
        }
    }

    /**
     * Deliver OTP via Brevo HTTP API (HTTPS port 443; never blocked on cloud free tiers).
     */
    protected function sendViaBrevoHttp(
        string $recipientEmail,
        string $otpCode,
        string $purpose,
        string $recipientName,
        string $apiKey,
        ?string $requestId
    ): EmailDeliveryResult {
        try {
            $mailable = new OtpMail($otpCode, $purpose, $recipientName);
            $appName = config('app.name', 'Smart Classroom Attendance System');
            $fromAddress = config('mail.from.address', 'osmenacolleges.attendance@gmail.com');

            $response = Http::withHeaders([
                'api-key'      => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://api.brevo.com/v3/smtp/email', [
                'sender'      => ['name' => $appName, 'email' => $fromAddress],
                'to'          => [['email' => $recipientEmail, 'name' => $recipientName]],
                'subject'     => "Your {$appName} Verification Code",
                'htmlContent' => $mailable->render(),
            ]);

            if ($response->successful()) {
                $json = $response->json();
                return EmailDeliveryResult::accepted(
                    provider: 'brevo (http api, port 443)',
                    messageId: $json['messageId'] ?? null,
                    response: 'HTTP 201 Created',
                    diagnostics: [
                        'transport'  => 'brevo_http',
                        'request_id' => $requestId,
                        'timestamp'  => now()->toIso8601String(),
                    ]
                );
            }

            return EmailDeliveryResult::rejected(
                provider: 'brevo (http api, port 443)',
                error: $response->body() ?: 'Brevo API returned an error',
                statusCode: $response->status()
            );
        } catch (Throwable $e) {
            return EmailDeliveryResult::rejected(
                provider: 'brevo (http api, port 443)',
                error: $this->sanitizeErrorMessage($e->getMessage()),
                statusCode: 500
            );
        }
    }
}
