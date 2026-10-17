<?php

namespace App\Filters;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Shared helpers for background requests.
 */
trait BackgroundResponse
{
    /**
     * JavaScript requests send `X-Requested-With: XMLHttpRequest`.
     */
    protected function isBackground(RequestInterface $request): bool
    {
        return $request instanceof IncomingRequest && $request->isAJAX();
    }

    /**
     * Error body `{"kode": …, "pesan": …}` plus extra fields.
     *
     * @param array<string, mixed> $extra
     */
    protected function jsonError(int $status, string $kode, string $pesan, array $extra = []): ResponseInterface
    {
        return service('response')
            ->setStatusCode($status)
            ->setJSON(['kode' => $kode, 'pesan' => $pesan] + $extra);
    }

    /**
     * Home page of the logged-in account type, or /login.
     */
    protected function homePage(): string
    {
        return session('admin_id') === null ? '/login' : '/panel';
    }
}
