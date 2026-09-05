<?php

namespace App\Services\Email;

class EmailDeliveryResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $provider,
        public readonly ?string $messageId = null,
        public readonly ?string $response = null,
        public readonly ?string $error = null,
        public readonly ?int $statusCode = 200,
        public readonly array $diagnostics = []
    ) {}

    public static function accepted(string $provider, ?string $messageId = null, ?string $response = null, array $diagnostics = []): self
    {
        return new self(
            success: true,
            provider: $provider,
            messageId: $messageId,
            response: $response,
            error: null,
            statusCode: 200,
            diagnostics: $diagnostics
        );
    }

    public static function rejected(string $provider, string $error, int $statusCode = 500, array $diagnostics = []): self
    {
        return new self(
            success: false,
            provider: $provider,
            messageId: null,
            response: null,
            error: $error,
            statusCode: $statusCode,
            diagnostics: $diagnostics
        );
    }
}
