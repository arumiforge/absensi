<?php

namespace App\Libraries;

/**
 * The ID of the current request: Nginx's `$request_id`,
 * passed to PHP as `REQUEST_ID`, written on every log line and shown as
 * the report code on the 500 page.
 */
final class RequestId
{
    private static ?string $fallback = null;

    /**
     * 32 lowercase hex characters. Without a valid `REQUEST_ID` (spark serve,
     * CLI, tests) a random ID is made once per request, in the same shape.
     */
    public static function get(): string
    {
        $id = $_SERVER['REQUEST_ID'] ?? null;

        if (is_string($id) && preg_match('/\A[0-9a-f]{32}\z/i', $id) === 1) {
            return strtolower($id);
        }

        return self::$fallback ??= bin2hex(random_bytes(16));
    }

    /**
     * First 8 characters in upper case, e.g. `7F3A2C1B`.
     */
    public static function reportCode(): string
    {
        return strtoupper(substr(self::get(), 0, 8));
    }
}
