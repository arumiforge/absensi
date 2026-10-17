<?php

namespace App\Libraries;

use App\Filters\Keamanan;
use CodeIgniter\Debug\ExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Exceptions;
use Throwable;

/**
 * Application exception handler.
 *
 * Pages: 400, 403, 404, 429, and 500 (any other status) with friendly
 * Indonesian text, never the exception message or trace. Background
 * requests (X-Requested-With) get a JSON code.
 * CLI requests and 5xx with display_errors on (development) keep the CI4
 * default, so developers still see the trace.
 */
class AppExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * Status => [JSON code, view under errors/html/].
     */
    private const PAGES = [
        400 => ['permintaan_rusak', 'error_400'],
        403 => ['ditolak', 'error_403'],
        404 => ['tidak_ditemukan', 'error_404'],
        429 => ['terlalu_sering', 'error_429'],
    ];

    public function __construct(private readonly Exceptions $config)
    {
    }

    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    ): void {
        if ($statusCode === 400) {
            // The message of CI4's SecurityException holds the rejected value, which may be a password.
            log_message('warning', 'Bad request rejected: ' . $exception::class);
        }

        if (! $request instanceof IncomingRequest || ($statusCode >= 500 && self::displayErrors())) {
            (new ExceptionHandler($this->config))->handle($exception, $request, $response, $statusCode, $exitCode);

            return;
        }

        self::prepare($statusCode, $request, $response);
        Keamanan::applyHeaders($request, $response);
        $response->send();

        if (ENVIRONMENT !== 'testing') {
            // @codeCoverageIgnoreStart
            exit($exitCode);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Fills the response with the error page or JSON code for this status.
     * Also used by the 404 override controller (App\Controllers\Galat).
     */
    public static function prepare(int $statusCode, IncomingRequest $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $response->setStatusCode($statusCode);
        } catch (HTTPException) {
            // Exception codes that are not HTTP statuses.
            $statusCode = 500;
            $response->setStatusCode($statusCode);
        }

        [$kode, $view] = self::PAGES[$statusCode] ?? ['galat_server', 'production'];
        $kodeLaporan   = RequestId::reportCode();

        $response->noCache(); // Replaces any cache header a controller set before the error

        if ($request->isAJAX()) {
            return $response->setJSON(['kode' => $kode, 'pesan' => self::message($statusCode, $kodeLaporan)]);
        }

        return $response->setContentType('text/html')->setBody(view('errors/html/' . $view, ['kodeLaporan' => $kodeLaporan]));
    }

    /**
     * Screen text for a status.
     */
    public static function message(int $statusCode, string $kodeLaporan): string
    {
        return match ($statusCode) {
            400     => lang('Galat.permintaanRusak'),
            403     => lang('Galat.diLuarHak'),
            404     => lang('Galat.tidakDitemukan'),
            429     => lang('Galat.terlaluSering'),
            default => lang('Galat.gangguan', [$kodeLaporan]),
        };
    }

    private static function displayErrors(): bool
    {
        return in_array(strtolower((string) ini_get('display_errors')), ['1', 'true', 'on', 'yes'], true);
    }
}
