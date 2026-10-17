<?php

use App\Services\Presensi;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class PresensiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    // Tuesday.
    private const HARI = '2026-10-13';

    private int $budi;

    protected function setUp(): void
    {
        parent::setUp();
        helper('format');
        $this->db->table('siswa')->insert(['nis' => '0012', 'nama' => 'Budi', 'kelas' => '7A', 'created_at' => '2026-10-01 08:00:00']);
        $this->budi = (int) $this->db->insertID();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    public function testStatusScanBoundaries(): void
    {
        $this->assertSame('hadir', Presensi::statusScan('06:59:59'));
        $this->assertSame('terlambat', Presensi::statusScan('07:00:00'));
        $this->assertSame('terlambat', Presensi::statusScan('07:59:59'));
        $this->assertNull(Presensi::statusScan('08:00:00'));
    }

    public function testScanBeforeSevenIsHadir(): void
    {
        $hasil = $this->scanPada('06:45:10');

        $this->assertSame('tercatat', $hasil['hasil']);
        $this->assertSame('hadir', $hasil['status']);
        $this->assertSame('06.45', $hasil['jam']);
        $this->seeInDatabase('presensi', ['siswa_id' => $this->budi, 'tanggal' => self::HARI, 'status' => 'hadir', 'jam_scan' => '06:45:10']);
    }

    public function testScanAfterSevenIsTerlambat(): void
    {
        $this->assertSame('terlambat', $this->scanPada('07:00:00')['status']);
    }

    public function testSecondScanKeepsFirstRecord(): void
    {
        $this->scanPada('06:50:00');
        $hasil = $this->scanPada('07:30:00');

        $this->assertSame('sudah', $hasil['hasil']);
        $this->assertSame('hadir', $hasil['status']);
        $this->assertSame('Budi sudah tercatat Hadir pukul 06.50.', $hasil['pesan']);
        $this->seeNumRecords(1, 'presensi', []);
    }

    public function testScanAfterEightIsRejected(): void
    {
        $hasil = $this->scanPada('08:00:00');

        $this->assertSame('galat', $hasil['hasil']);
        $this->assertStringContainsString('ditutup pukul 08.00', $hasil['pesan']);
        $this->seeNumRecords(0, 'presensi', []);
    }

    public function testUnknownCodeIsRejected(): void
    {
        Time::setTestNow(self::HARI . ' 06:30:00', 'Asia/Jakarta');

        $this->assertSame('galat', (new Presensi())->scan('9999')['hasil']);
    }

    public function testNoScanOnSundayOrHoliday(): void
    {
        Time::setTestNow('2026-10-18 06:30:00', 'Asia/Jakarta');
        $this->assertStringContainsString('Hari Minggu', (new Presensi())->scan('0012')['pesan']);

        $this->db->table('libur')->insert(['tanggal' => self::HARI, 'keterangan' => 'Rapat guru']);
        $this->assertStringContainsString('Rapat guru', $this->scanPada('06:30:00')['pesan']);
        $this->seeNumRecords(0, 'presensi', []);
    }

    public function testRekapMarksAlpaOnlyAfterEight(): void
    {
        Time::setTestNow(self::HARI . ' 07:59:00', 'Asia/Jakarta');
        $this->assertNull((new Presensi())->rekap(self::HARI)['baris'][0]['status']);

        Time::setTestNow(self::HARI . ' 08:00:00', 'Asia/Jakarta');
        $rekap = (new Presensi())->rekap(self::HARI);
        $this->assertSame('alpa', $rekap['baris'][0]['status']);
        $this->assertSame(1, $rekap['jumlah']['alpa']);

        // Past days are closed whatever the time now.
        Time::setTestNow('2026-10-14 06:00:00', 'Asia/Jakarta');
        $this->assertSame('alpa', (new Presensi())->rekap(self::HARI)['baris'][0]['status']);
    }

    public function testRekapNoAlpaOnHolidayOrForNewStudents(): void
    {
        Time::setTestNow(self::HARI . ' 09:00:00', 'Asia/Jakarta');
        $this->db->table('siswa')->insert(['nis' => '0013', 'nama' => 'Ani', 'kelas' => '7A', 'created_at' => self::HARI . ' 08:30:00']);
        $this->db->table('siswa')->insert(['nis' => '0014', 'nama' => 'Citra', 'kelas' => '7B', 'created_at' => '2026-10-14 08:30:00']);

        $rekap = (new Presensi())->rekap(self::HARI);
        $this->assertSame(['Ani' => null, 'Budi' => 'alpa'], array_column($rekap['baris'], 'status', 'nama'));
        $this->assertSame(1, $rekap['jumlah']['belum']);

        $this->db->table('libur')->insert(['tanggal' => self::HARI, 'keterangan' => 'Rapat guru']);
        $rekap = (new Presensi())->rekap(self::HARI, '7A');
        $this->assertSame('Rapat guru', $rekap['libur']);
        $this->assertSame(2, $rekap['jumlah']['belum']);
    }

    public function testManualStatusKeepsScanTimeAndCanBeCleared(): void
    {
        $this->scanPada('07:10:00');

        (new Presensi())->ubah($this->budi, self::HARI, 'dispensasi', 'Lomba');
        $this->seeInDatabase('presensi', ['siswa_id' => $this->budi, 'status' => 'dispensasi', 'keterangan' => 'Lomba', 'jam_scan' => '07:10:00']);

        (new Presensi())->ubah($this->budi, '2026-10-12', 'sakit', '');
        $this->seeInDatabase('presensi', ['tanggal' => '2026-10-12', 'status' => 'sakit', 'keterangan' => null, 'jam_scan' => null]);

        (new Presensi())->ubah($this->budi, self::HARI, '', '');
        $this->dontSeeInDatabase('presensi', ['tanggal' => self::HARI]);
    }

    private function scanPada(string $jam): array
    {
        Time::setTestNow(self::HARI . ' ' . $jam, 'Asia/Jakarta');

        return (new Presensi())->scan(' 0012 ');
    }
}
