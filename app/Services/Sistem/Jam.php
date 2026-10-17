<?php

namespace App\Services\Sistem;

use CodeIgniter\I18n\Time;

/**
 * The only source of "now" for services.
 *
 * Built on Time::now(), so tests can freeze it with Time::setTestNow().
 * Queries never use NOW(), CURDATE() or CURRENT_TIMESTAMP; pass these
 * values as parameters instead.
 */
class Jam
{
    private const ZONE = 'Asia/Jakarta';

    /**
     * Current time in WIB.
     */
    public function now(): Time
    {
        return Time::now(self::ZONE);
    }

    /**
     * Today's date in WIB as `YYYY-MM-DD`.
     */
    public function today(): string
    {
        return $this->now()->toDateString();
    }
}
