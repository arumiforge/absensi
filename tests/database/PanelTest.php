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

    public function testScanPageCarriesLocalData(): void
    {
        $page = $this->kirim('GET', 'panel/scan');

        $page->assertSee('jsQR.min.js');
        preg_match('#<script type="application/json" id="data-scan">(.*?)</script>#s', (string) $page->response()->getBody(), $m);
        $data = json_decode($m[1], true);
        $this->assertSame('2026-10-13', $data['tanggal']);
        $this->assertSame(['Budi', '7A'], $data['siswa']['0012']);
    }

    public function testScanBatchRecordsAttendance(): void
    {
        $result = $this->kirimJson('panel/scan', ['scan' => [['id' => 'x1', 'nis' => '0012', 'waktu' => '2026-10-13 06:35:00']]]);

        $result->assertOK();
        $json = json_decode($result->getJSON(), true);
        $this->assertSame([['id' => 'x1', 'hasil' => 'tercatat']], array_map(static fn (array $h): array => ['id' => $h['id'], 'hasil' => $h['hasil']], $json['hasil']));
        $this->assertIsInt($json['jam']);
        $this->seeInDatabase('presensi', ['tanggal' => '2026-10-13', 'status' => 'hadir', 'jam_scan' => '06:35:00']);
    }

    public function testScanBatchRejectsBadBody(): void
    {
        $this->kirimJson('panel/scan', ['scan' => [['id' => 'x1', 'nis' => '0012']]])->assertStatus(400);
        $this->kirimJson('panel/scan', ['scan' => array_fill(0, 101, ['id' => 'x', 'nis' => '0012', 'waktu' => '2026-10-13 06:35:00'])])->assertStatus(400);
        $this->seeNumRecords(0, 'presensi', []);
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
     * A background JSON request from the scan page, as the admin.
     */
    private function kirimJson(string $uri, array $body): TestResponse
    {
        Services::resetSingle('router');
        Services::resetSingle('response');

        return $this->withSession($this->sesi)
            ->withHeaders(['X-CSRF-TOKEN' => service('security')->getHash(), 'X-Requested-With' => 'XMLHttpRequest', 'Content-Type' => 'application/json'])
            ->withBody(json_encode($body))
            ->call('POST', $uri);
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
