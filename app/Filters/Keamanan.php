<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter `keamanan` in `$required['after']`: per-area Content-Security-Policy
 * and Permissions-Policy. The exception handler
 * calls applyHeaders() for 403 and 500 pages.
 */
class Keamanan implements FilterInterface
{
    private const CSP = [
        'default-src' => "'self'",
        'script-src'  => "'self'",
        'style-src'   => "'self'",
        // data: for Bootstrap's inline SVG icons.
        'img-src'         => "'self' data:",
        'font-src'        => "'self'",
        'connect-src'     => "'self'",
        'frame-src'       => "'self'",
        'object-src'      => "'none'",
        'worker-src'      => "'none'",
        'base-uri'        => "'none'",
        'form-action'     => "'self'",
        'frame-ancestors' => "'none'",
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        self::applyHeaders($request, $response);

        return $response;
    }

    /**
     * Writes CSP (unless the controller already wrote one, e.g. for files)
     * and Permissions-Policy for the request's area.
     */
    public static function applyHeaders(RequestInterface $request, ResponseInterface $response): void
    {
        $path    = $request instanceof IncomingRequest ? trim($request->getPath(), '/') : '';
        // Only the scan page may use the camera.
        $isScan = $path === 'panel/scan';

        if (! $response->hasHeader('Content-Security-Policy') && ! $response->hasHeader('Content-Security-Policy-Report-Only')) {
            $value = implode('; ', array_map(static fn ($name, $source) => "{$name} {$source}", array_keys(self::CSP), self::CSP));

            // Report-only in development: the Debug Toolbar uses inline scripts.
            $response->setHeader(ENVIRONMENT === 'development' ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy', $value);
        }

        if (! $response->hasHeader('Permissions-Policy')) {
            $response->setHeader('Permissions-Policy', ($isScan ? 'camera=(self)' : 'camera=()') . ', microphone=(), geolocation=(), payment=(), usb=()');
        }
    }
}
