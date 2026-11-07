<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/** Base exception for failures that may be safely shown to application users. */
class AppException extends RuntimeException
{
    private const SENSITIVE_CONTEXT_KEYS = '/password|otp|token|secret|credential|biometric|face|image|photo|authorization|cookie|session|phone|email|student[_-]?id|account[_-]?id|user[_-]?id|payload|request|body/i';

    public function __construct(
        string $message = 'The request could not be completed.',
        private readonly string $errorCode = 'APP_ERROR',
        private readonly array $context = [],
        private readonly int $httpStatus = 400,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function errorCode(): string
    {
        return preg_match('/^[A-Z][A-Z0-9_]{1,63}$/', $this->errorCode) ? $this->errorCode : 'APP_ERROR';
    }

    public function httpStatus(): int
    {
        return $this->httpStatus >= 400 && $this->httpStatus <= 599 ? $this->httpStatus : 400;
    }

    /** Return only bounded, non-sensitive diagnostic metadata for application logs. */
    public function safeContext(): array
    {
        return $this->filterContext($this->context);
    }

    private function filterContext(array $context, int $depth = 0): array
    {
        if ($depth >= 4) {
            return [];
        }

        $safe = [];
        foreach ($context as $key => $value) {
            $key = (string) $key;
            if (preg_match(self::SENSITIVE_CONTEXT_KEYS, $key)) {
                continue;
            }

            if (is_array($value)) {
                $safe[$key] = $this->filterContext($value, $depth + 1);
            } elseif (is_bool($value) || is_int($value) || is_float($value)) {
                $safe[$key] = $value;
            } elseif (is_string($value)) {
                $safe[$key] = mb_substr($value, 0, 200);
            }
        }

        return $safe;
    }
}
