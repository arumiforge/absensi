<?php

use App\Controllers\Galat;
use App\Libraries\AppExceptionHandler;
use App\Libraries\RequestId;
use App\Libraries\RequestLogHandler;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use Config\Exceptions;
use Config\Services;

/**
 * Error pages, report code, and log lines.
 *
 * @internal
 */
final class ErrorPageTest extends CIUnitTestCase
{
    use ControllerTestTrait;

    private const REQUEST_ID = '7f3a2c1b9d8e4f60a1b2c3d4e5f60718';

    private string $displayErrors;

    protected function setUp(): void
    {
        parent::setUp();

        // Production: display_errors is off, so no trace page.
        $this->displayErrors = (string) ini_get('display_errors');
        ini_set('display_errors', '0');
        service('language')->setLocale('id');
        $_SERVER['REQUEST_ID'] = self::REQUEST_ID;
    }

    protected function tearDown(): void
    {
        ini_set('display_errors', $this->displayErrors);
        unset($_SERVER['REQUEST_ID']);
        $this->resetServices();

        parent::tearDown();
    }

    public function testProduction500ShowsReportCodeWithoutTrace(): void
    {
        $response = $this->handle(new RuntimeException('SECRET table siswa'), 500);
        $body     = $response->getBody();

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('no-store', $response->getHeaderLine('Cache-Control'));
        $this->assertStringContainsString('Bila berulang, laporkan kode 7F3A2C1B ke admin sekolah.', $body);
        $this->assertNoTrace($body);
    }

    public function testReportCodeFallsBackWithoutValidRequestId(): void
    {
        foreach ([null, 'abc', str_repeat('z', 32)] as $id) {
            $_SERVER['REQUEST_ID'] = $id;

            $code = RequestId::reportCode();

            $this->assertMatchesRegularExpression('/\A[0-9A-F]{8}\z/', $code);
            $this->assertSame($code, RequestId::reportCode(), 'Same code for the whole request');
            $this->assertStringContainsString($code, $this->handle(new RuntimeException('x'), 500)->getBody());
        }
    }

    public function testRequestIdIsLowercased(): void
    {
        $_SERVER['REQUEST_ID'] = strtoupper(self::REQUEST_ID);

        $this->assertSame(self::REQUEST_ID, RequestId::get());
    }

    public function testProduction404FromHandler(): void
    {
        $response = $this->handle(PageNotFoundException::forPageNotFound('SECRET missing route'), 404);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('Halaman tidak ditemukan.', $response->getBody());
        $this->assertNoTrace($response->getBody());
    }

    public function testProduction404FromOverrideController(): void
    {
        $result = $this->controller(Galat::class)->execute('tidakDitemukan');

        $result->assertStatus(404);
        $result->assertSee('Halaman tidak ditemukan.');
        $this->assertNoTrace($result->getBody());
    }

    public function testForbiddenPageText(): void
    {
        $this->assertStringContainsString('Anda tidak berhak membuka data ini.', $this->handle(new RuntimeException('x'), 403, 'panel/siswa')->getBody());
    }

    public function testBadRequestAndTooManyRequestsPages(): void
    {
        $response = $this->handle(SecurityException::forInvalidUTF8Chars('POST', 'SECRET-password'), 400);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Permintaan tidak dapat diproses. Muat ulang halaman, lalu coba lagi.', $response->getBody());
        $this->assertNoTrace($response->getBody());
        $this->assertLogged('warning', 'Bad request rejected: ' . SecurityException::class);

        $response = $this->handle(new RuntimeException('x'), 429);
        $this->assertStringContainsString('Terlalu banyak permintaan. Tunggu sebentar, lalu coba lagi.', $response->getBody());
    }

    public function testBackgroundRequestsGetJsonCode(): void
    {
        $response = $this->handle(new RuntimeException('SECRET'), 500, 'kiosk/api/v1/sinkron', true);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(
            ['kode' => 'galat_server', 'pesan' => 'Terjadi gangguan. Coba lagi beberapa saat lagi. Bila berulang, laporkan kode 7F3A2C1B ke admin sekolah.'],
            json_decode($response->getBody(), true),
        );

        $response = $this->handle(PageNotFoundException::forPageNotFound(), 404, 'panel/x', true);
        $this->assertSame('tidak_ditemukan', json_decode($response->getBody(), true)['kode']);
    }

    public function testErrorPageLoadsBootstrapWithVersion(): void
    {
        $body  = $this->handle(new RuntimeException('x'), 500)->getBody();
        $versi = config('Spensada')->versi;

        $this->assertStringContainsString('bootstrap.min.css?v=' . $versi . '"', $body);
        $this->assertStringContainsString('href="/favicon.ico"', $body);
        $this->assertStringNotContainsString('style=', $body);
        $this->assertStringNotContainsString('<script', $body);
    }

    public function testLogLinesStartWithRequestId(): void
    {
        $dir = sys_get_temp_dir() . '/spensada-log-' . bin2hex(random_bytes(4)) . '/';
        mkdir($dir);

        try {
            $handler = new RequestLogHandler(['handles' => ['warning'], 'path' => $dir]);
            $handler->handle('warning', 'Hello');

            $files = glob($dir . '*.log');
            $this->assertCount(1, $files);
            $this->assertStringContainsString('--> [' . self::REQUEST_ID . ' CLI] Hello', file_get_contents($files[0]));
        } finally {
            array_map(unlink(...), glob($dir . '*'));
            rmdir($dir);
        }
    }

    private function handle(Throwable $exception, int $status, string $path = 'panel', bool $ajax = false): ResponseInterface
    {
        $request = new IncomingRequest(config('App'), new SiteURI(config('App'), $path), null, new UserAgent());

        if ($ajax) {
            $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        }

        $response = Services::response(null, false);

        ob_start();
        (new AppExceptionHandler(new Exceptions()))->handle($exception, $request, $response, $status, 1);
        $output = ob_get_clean();

        $this->assertSame($response->getBody(), $output);

        return $response;
    }

    private function assertNoTrace(string $body): void
    {
        // View path comments come from CI_DEBUG, which is off in production.
        $body = preg_replace('/<!-- DEBUG-VIEW [^>]* -->/', '', $body);

        foreach (['SECRET', 'Exception', '.php', 'line ', 'Trace'] as $needle) {
            $this->assertStringNotContainsString($needle, $body);
        }
    }
}
