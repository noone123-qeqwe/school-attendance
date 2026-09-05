<?php

namespace App\Services\Email;

use App\Mail\OtpMail;
use Illuminate\Mail\SentMessage;
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

        // 1. Guard against unconfigured or dummy email drivers (prevents false success)
        if (!$this->isOutboundDeliveryConfigured()) {
            $errorMsg = 'Outbound email delivery is not configured. Please configure valid SMTP or API credentials in your environment.';
            Log::error("Email delivery aborted [provider: {$providerName}]: {$errorMsg}");
            return EmailDeliveryResult::rejected($providerName, $errorMsg, 500);
        }

        // 2. Transmit email via Laravel Mailer
        try {
            $mailable = new OtpMail($otpCode, $purpose, $recipientName);
            $sentMessage = Mail::to($recipientEmail)->send($mailable);

            $messageId = $this->extractMessageId($sentMessage);
            $debugResponse = $this->extractDebugResponse($sentMessage);

            return EmailDeliveryResult::accepted(
                provider: $providerName,
                messageId: $messageId,
                response: $debugResponse,
                diagnostics: [
                    'mailer'     => config('mail.default'),
                    'request_id' => $requestId,
                    'timestamp'  => now()->toIso8601String(),
                ]
            );
        } catch (Throwable $e) {
            $maskedError = $this->sanitizeErrorMessage($e->getMessage());
            Log::error("Email delivery failed [provider: {$providerName}]: {$maskedError}", [
                'exception_class' => get_class($e),
                'code'            => $e->getCode(),
            ]);

            return EmailDeliveryResult::rejected(
                provider: $providerName,
                error: 'Unable to send verification code. Please try again.',
                statusCode: 500,
                diagnostics: [
                    'internal_error' => $maskedError,
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

            $sentMessage = Mail::raw(
                "Hello,\n\nThis is an automated diagnostic test email from {$appName}.\n\n" .
                "Diagnostic Details:\n" .
                "- Sent: {$timestamp}\n" .
                "- Active Provider: {$providerName}\n" .
                "- Server Time: " . now()->format('Y-m-d H:i:s T') . "\n\n" .
                "If you are reading this in Gmail, your email delivery pipeline is working correctly.\n",
                function ($message) use ($recipientEmail, $appName) {
                    $message->to($recipientEmail)
                        ->subject("{$appName} - Diagnostic Delivery Test (" . date('H:i:s') . ")");
                }
            );

            $messageId = $this->extractMessageId($sentMessage);
            $debugResponse = $this->extractDebugResponse($sentMessage);

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
}
