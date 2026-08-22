<?php

namespace App\Traits;

trait SanitizesExport
{
    /**
     * Sanitize a field to prevent CSV / Formula Injection (DDE).
     * Prefixes potentially dangerous characters with a single quote.
     */
    protected function sanitizeField($value)
    {
        if ($value === null || !is_string($value)) {
            return $value;
        }

        $trimmed = ltrim($value);
        if ($trimmed === '') {
            return $value;
        }

        $firstChar = $trimmed[0];
        // Characters that can trigger formula execution in Excel/Calc
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r", '%'], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
