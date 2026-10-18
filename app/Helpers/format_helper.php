<?php

/**
 * Indonesian locale formatting for everything shown to users.
 *
 * Every view, export, PDF, and message MUST format dates, times, and
 * numbers through these functions. Do not call date(), number_format(),
 * or IntlDateFormatter directly for user-facing output.
 *
 * Formats follow .claude/memory/locale-format.md.
 * Time zone is always Asia/Jakarta (WIB).
 */

if (! function_exists('format_tz')) {
    /**
     * The only time zone used for user-facing output.
     */
    function format_tz(): DateTimeZone
    {
        static $tz = null;

        return $tz ??= new DateTimeZone('Asia/Jakarta');
    }
}

if (! function_exists('format_to_datetime')) {
    /**
     * Normalizes a date input to a DateTimeImmutable in Asia/Jakarta.
     *
     * Strings without an offset are read as WIB, because the database
     * session runs in WIB. Integers are Unix timestamps.
     */
    function format_to_datetime(DateTimeInterface|int|string|null $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value)->setTimezone(format_tz());
        }

        if (is_int($value)) {
            return (new DateTimeImmutable('@' . $value))->setTimezone(format_tz());
        }

        return (new DateTimeImmutable($value, format_tz()))->setTimezone(format_tz());
    }
}

if (! function_exists('format_date')) {
    /**
     * Formats a date for display.
     *
     * Styles:
     * - 'long'    13 Oktober 2026          (sentences and forms; default)
     * - 'full'    Selasa, 13 Oktober 2026  (page titles, dashboard, slips, flyers)
     * - 'short'   13 Okt 2026              (tables and lists)
     * - 'day'     13 Okt                   (tables within a single year)
     * - 'numeric' 13/10/2026               (compact fields, date input hints)
     *
     * Returns an empty string for null or empty input.
     */
    function format_date(DateTimeInterface|int|string|null $value, string $style = 'long'): string
    {
        $date = format_to_datetime($value);

        if ($date === null) {
            return '';
        }

        $pattern = match ($style) {
            'long'    => 'd MMMM y',
            'full'    => 'EEEE, d MMMM y',
            'short'   => 'd MMM y',
            'day'     => 'd MMM',
            'numeric' => 'dd/MM/y',
            default   => throw new InvalidArgumentException('Unknown date style: ' . $style),
        };

        return format_icu($date, $pattern);
    }
}

if (! function_exists('format_time')) {
    /**
     * Formats a time as 07.00, or 07.00.59 with seconds (24-hour, dot separator).
     */
    function format_time(DateTimeInterface|int|string|null $value, bool $withSeconds = false): string
    {
        $date = format_to_datetime($value);

        if ($date === null) {
            return '';
        }

        return $date->format($withSeconds ? 'H.i.s' : 'H.i');
    }
}

if (! function_exists('format_datetime')) {
    /**
     * Formats a date and time as "13 Okt 2026, 07.32".
     */
    function format_datetime(DateTimeInterface|int|string|null $value, bool $withSeconds = false): string
    {
        $date = format_to_datetime($value);

        if ($date === null) {
            return '';
        }

        return format_date($date, 'short') . ', ' . format_time($date, $withSeconds);
    }
}

if (! function_exists('format_number')) {
    /**
     * Formats a number with a dot thousands separator and comma decimals: 1.024 or 12,5.
     */
    function format_number(float|int|string|null $value, int $decimals = 0): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format(format_pre_round($value), $decimals, ',', '.');
    }
}

if (! function_exists('format_pre_round')) {
    /**
     * Rounds to 15 significant digits first, so float noise does not decide
     * a half: 23 / 40 * 100 is 57.49999999999999 and must round as 57.5.
     * Done explicitly so the result does not depend on the PHP version's
     * round().
     */
    function format_pre_round(float|int|string $value): float
    {
        return (float) sprintf('%.15g', (float) $value);
    }
}

if (! function_exists('format_icu')) {
    /**
     * Runs an ICU date pattern with the id_ID locale in Asia/Jakarta.
     *
     * @internal Use format_date() instead.
     */
    function format_icu(DateTimeImmutable $date, string $pattern): string
    {
        static $formatters = [];

        $formatters[$pattern] ??= new IntlDateFormatter(
            'id_ID',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            format_tz(),
            IntlDateFormatter::GREGORIAN,
            $pattern,
        );

        $result = $formatters[$pattern]->format($date);

        if ($result === false) {
            throw new RuntimeException('Date formatting failed: ' . intl_get_error_message());
        }

        return $result;
    }
}
