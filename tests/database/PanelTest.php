<?php

use App\Filters\Sesi;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;

/**
 * @internal
 */
final class PanelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    private array $sesi;

    protected function setUp(): void
    {
        foreach (['routes', 'router', 'request', 'response'] as $service) {
            Services::resetSingle($service);
        }
        parent::setUp();
        Time::setTestNow('2026-10-13 06:40:00', 'Asia/Jakarta');

        $hash = password_hash('rahasia-123', PASSWORD_DEFAULT);
        $this->db->table('admin')->insert(['username' => 'admin', 'password_hash' => $hash]);
        $this->sesi = ['admin_id' => (int) $this->db->insertID(), 'cap' => Sesi::cap($hash)];
        $this->db->table('siswa')->insert(['nis' => '0012', 'nama' => 'Budi', 'kelas' => '7A', 'created_at' => '2026-10-01 08:00:00']);
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    public function testLogin(): void
    {
        $this->kirim('POST', 'login', ['username' => 'admin', 'password' => 'salah'], [])->assertRedirectTo('https://example.com/login');
        $this->assertSame('Username atau password belum cocok. Coba lagi.', session('galat'));

        $this->kirim('POST', 'login', ['username' => 'admin', 'password' => 'rahasia-123'], [])->assertRedirectTo('https://example.com/panel');
        $this->assertSame($this->sesi['admin_id'], session('admin_id'));
    }

    public function testPanelNeedsLogin(): void
    {
        $this->kirim('GET', 'panel/rekap', [], [])->assertRedirectTo('https://example.com/login');
    }

    public function testScanRecordsAttendance(): void
    {
        $this->kirim('GET', 'panel/scan')->assertSee('jsQR.min.js');

        $result = $this->kirim('POST', 'panel/scan', ['nis' => '0012']);

        $result->assertOK();
        $this->assertSame('tercatat', json_decode($result->getJSON(), true)['hasil']);
        $this->seeInDatabase('presensi', ['tanggal' => '2026-10-13', 'status' => 'hadir']);
    }

    public function testRekapPageAndManualStatus(): void
    {
        $rekap = $this->kirim('GET', 'panel/rekap?tanggal=2026-10-13');
        $rekap->assertSee('Selasa, 13 Oktober 2026');
        $rekap->assertSee('Budi');

        $siswa = $this->db->table('siswa')->get()->getRowArray();
        $this->kirim('POST', 'panel/rekap', ['siswa_id' => $siswa['id'], 'tanggal' => '2026-10-13', 'status' => 'izin', 'keterangan' => 'Acara keluarga', 'kelas' => '7A'])
            ->assertRedirectTo('https://example.com/panel/rekap?tanggal=2026-10-13&kelas=7A#siswa-' . $siswa['id']);
        $this->seeInDatabase('presensi', ['siswa_id' => $siswa['id'], 'status' => 'izin', 'keterangan' => 'Acara keluarga']);

        $this->kirim('POST', 'panel/rekap', ['siswa_id' => $siswa['id'], 'tanggal' => '2026-13-40', 'status' => 'izin']);
        $this->assertStringContainsString('Data belum lengkap', session('galat'));
    }

    public function testRekapDownloads(): void
    {
        $excel = $this->kirim('GET', 'panel/rekap/excel?tanggal=2026-10-13&kelas=7A');
        $excel->assertHeader('Content-Disposition', 'attachment; filename="rekap-presensi-20261013-7A.xlsx"');
        $this->assertStringStartsWith('PK', $excel->response()->getBody());

        $pdf = $this->kirim('GET', 'panel/rekap/pdf?tanggal=2026-10-13');
        $pdf->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->response()->getBody());
    }

    public function testStudentCrud(): void
    {
        $this->kirim('POST', 'panel/siswa', ['nis' => '0012', 'nama' => 'Ani', 'kelas' => '7B']);
        $this->assertSame('NIS ini sudah dipakai siswa lain.', session('galat'));

        $this->kirim('POST', 'panel/siswa', ['nis' => '0013', 'nama' => 'Ani', 'kelas' => '7B'])->assertRedirectTo('https://example.com/panel/siswa');
        $ani = $this->db->table('siswa')->where('nis', '0013')->get()->getRowArray();

        $this->kirim('POST', "panel/siswa/{$ani['id']}", ['nis' => '0013', 'nama' => 'Ani Lestari', 'kelas' => '7B']);
        $this->seeInDatabase('siswa', ['id' => $ani['id'], 'nama' => 'Ani Lestari']);

        $cari = $this->kirim('GET', 'panel/siswa?cari=lestari');
        $cari->assertSee('Ani Lestari');
        $cari->assertDontSee('Budi');

        $this->kirim('POST', "panel/siswa/{$ani['id']}/hapus");
        $this->dontSeeInDatabase('siswa', ['id' => $ani['id']]);
    }

    public function testHoliday(): void
    {
        $this->kirim('POST', 'panel/libur', ['tanggal' => '2026-10-20', 'keterangan' => 'Rapat guru'])->assertRedirectTo('https://example.com/panel/libur');
        $this->kirim('GET', 'panel/libur')->assertSee('Selasa, 20 Oktober 2026');

        $this->kirim('POST', 'panel/libur/2026-10-20/hapus');
        $this->dontSeeInDatabase('libur', ['tanggal' => '2026-10-20']);
    }

    public function testChangePasswordEndsOtherSessions(): void
    {
        $this->kirim('POST', 'panel/password', ['password_lama' => 'salah', 'password_baru' => 'baru-123456', 'password_ulang' => 'baru-123456']);
        $this->assertSame('Password lama belum cocok. Coba lagi.', session('galat'));

        $this->kirim('POST', 'panel/password', ['password_lama' => 'rahasia-123', 'password_baru' => 'baru-123456', 'password_ulang' => 'baru-123456']);
        $this->assertSame('Password sudah diganti.', session('sukses'));

        $this->kirim('GET', 'panel/rekap')->assertRedirectTo('https://example.com/login');
    }

    /**
     * One request with a valid CSRF token, as the admin unless another session is given.
     */
    private function kirim(string $method, string $uri, array $isian = [], ?array $sesi = null): TestResponse
    {
        // The shared router keeps the method of the last request.
        Services::resetSingle('router');
        Services::resetSingle('response');

        return $this->withSession($sesi ?? $this->sesi)
            ->withHeaders(['X-CSRF-TOKEN' => service('security')->getHash()])
            ->call($method, $uri, $isian);
    }
}
