<?php

namespace App\Libraries;

use CodeIgniter\Log\Handlers\FileHandler;

/**
 * CI4 file log handler that starts every line with the request ID, method,
 * and path without query string. The query
 * string is left out because it can hold searched NISNs or names.
 */
class RequestLogHandler extends FileHandler
{
    public function handle($level, $message): bool
    {
        if (is_cli()) {
            $where = 'CLI';
        } else {
            $method = $_SERVER['REQUEST_METHOD'] ?? '-';
            $path   = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
            $where  = $method . ' ' . (is_string($path) && $path !== '' ? $path : '/');
        }

        return parent::handle($level, '[' . RequestId::get() . ' ' . $where . '] ' . $message);
    }
}
