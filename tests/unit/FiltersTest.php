<?php

use App\Filters\Keamanan;
use App\Filters\Sesi;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\FilterTestTrait;
use Config\Services;

/**
 * @internal
 */
final class FiltersTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use FilterTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];

    protected function setUp(): void
    {
        // Request and response are shared services; start each test clean.
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();

        $ok = static fn (): string => 'ok';
        $this->withRoutes([
            ['GET', 'panel/uji', $ok, ['filter' => 'sesi']],
            ['GET', 'panel/scan', $ok],
            ['POST', 'panel/simpan', $ok],
        ]);
    }

    public function testCsrfRejectionAnswersJsonWithNewToken(): void
    {
        $result = $this->withHeaders(self::AJAX)->post('panel/simpan');

        $result->assertStatus(403);
        $body = json_decode($result->getJSON(), true);
        $this->assertSame('csrf', $body['kode']);
        $this->assertSame(config('Security')->headerName, $body['csrf']['header']);
        $this->assertNotEmpty($body['csrf']['token']);
    }

    public function testCsrfRejectionOfFormRedirectsBackWithInputExceptSecrets(): void
    {
        $result = $this->withHeaders(['Referer' => 'http://example.com/panel/siswa/tambah?x=1'])
            ->post('panel/simpan', ['nama' => 'Budi', 'password' => 'rahasia', 'pin_ulang' => '123456']);

        $result->assertStatus(303);
        $result->assertRedirectTo('http://example.com/panel/siswa/tambah?x=1');
        $this->assertStringContainsString('terlalu lama dibuka', session('galat'));
        $this->assertSame(['nama' => 'Budi'], session('_ci_old_input')['post']);
    }

    public function testCsrfRejectionIgnoresForeignReferer(): void
    {
        $result = $this->withSession(['admin_id' => 5])
            ->withHeaders(['Referer' => 'https://evil.test/panel'])
            ->post('panel/simpan');

        $result->assertRedirectTo('https://example.com/panel');
    }

    public function testTooLargeBodyAnswersTerlaluBesar(): void
    {
        $server  = service('superglobals')->getServerArray();
        $request = service('request')->withMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $request->setGlobal('server', ['CONTENT_LENGTH' => (string) PHP_INT_MAX] + $server);
        $this->request = $request;

        try {
            $result = $this->getFilterCaller('csrf', 'before')();
        } finally {
            service('superglobals')->setGlobalArray('server', $server);
        }

        $this->assertSame(413, $result->getStatusCode());
        $this->assertSame('terlalu_besar', json_decode($result->getBody(), true)['kode']);
    }

    public function testSesiWithoutLoginAnswersLoginUlang(): void
    {
        $result = $this->withHeaders(self::AJAX)->get('panel/uji');

        $result->assertStatus(401);
        $this->assertSame('login_ulang', json_decode($result->getJSON(), true)['kode']);
    }

    public function testSesiWithoutLoginRedirectsPageToLogin(): void
    {
        $this->get('panel/uji')->assertRedirectTo('https://example.com/login');
    }

    public function testSesiPassesForAdmin(): void
    {
        $this->withSession($this->sesiAdmin())->get('panel/uji')->assertOK();
    }

    public function testSesiRejectsSessionAfterPasswordChange(): void
    {
        $session = $this->sesiAdmin();
        $this->db->table('admin')->update(['password_hash' => password_hash('lain-lagi-123', PASSWORD_DEFAULT)]);

        $this->withSession($session)->get('panel/uji')->assertRedirectTo('https://example.com/login');
        $this->assertNull(session('admin_id'));
    }

    public function testCameraOnlyOnScanPage(): void
    {
        $this->get('panel/scan')->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=(), usb=()');

        Services::resetSingle('response');
        $result = $this->withSession($this->sesiAdmin())->get('panel/uji');
        $result->assertHeader(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-src 'self'; object-src 'none'; worker-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'",
        );
        $result->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    }

    public function testApplyHeadersKeepsControllerCsp(): void
    {
        $response = service('response');
        $response->setHeader('Content-Security-Policy', "sandbox; default-src 'none'; frame-ancestors 'none'");

        Keamanan::applyHeaders(service('request'), $response);

        $this->assertSame("sandbox; default-src 'none'; frame-ancestors 'none'", $response->getHeaderLine('Content-Security-Policy'));
    }

    private function sesiAdmin(): array
    {
        $hash = password_hash('rahasia-123', PASSWORD_DEFAULT);
        $this->db->table('admin')->insert(['username' => 'admin', 'password_hash' => $hash]);

        return ['admin_id' => (int) $this->db->insertID(), 'cap' => Sesi::cap($hash)];
    }
}
