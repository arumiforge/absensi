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

    public function testBatchUsesDeviceTimeNotArrivalTime(): void
    {
        // Scanned offline before 07:00, sent at 07:20.
        Time::setTestNow(self::HARI . ' 07:20:00', 'Asia/Jakarta');
        $hasil = (new Presensi())->scanBanyak([
            ['id' => 'a', 'nis' => '0012', 'waktu' => self::HARI . ' 06:55:30'],
            ['id' => 'b', 'nis' => '0012', 'waktu' => self::HARI . ' 07:05:00'],
            ['id' => 'c', 'nis' => '9999', 'waktu' => self::HARI . ' 06:56:00'],
        ]);

        $this->assertSame(['a', 'b', 'c'], array_column($hasil, 'id'));
        $this->assertSame(['tercatat', 'sudah', 'galat'], array_column($hasil, 'hasil'));
        $this->seeInDatabase('presensi', ['siswa_id' => $this->budi, 'tanggal' => self::HARI, 'status' => 'hadir', 'jam_scan' => '06:55:30']);
        $this->seeNumRecords(1, 'presensi', []);
    }

    public function testBatchSentAgainKeepsOneRecord(): void
    {
        Time::setTestNow(self::HARI . ' 07:20:00', 'Asia/Jakarta');
        $scan = [['id' => 'a', 'nis' => '0012', 'waktu' => self::HARI . ' 07:10:00']];

        (new Presensi())->scanBanyak($scan);
        $this->assertSame('sudah', (new Presensi())->scanBanyak($scan)[0]['hasil']);
        $this->seeNumRecords(1, 'presensi', []);
    }

    public function testBatchRejectsFutureOrUnreadableTime(): void
    {
        Time::setTestNow(self::HARI . ' 06:30:00', 'Asia/Jakarta');
        $hasil = (new Presensi())->scanBanyak([
            ['id' => 'a', 'nis' => '0012', 'waktu' => self::HARI . ' 06:40:00'],
            ['id' => 'b', 'nis' => '0012', 'waktu' => '2026-02-30 06:20:00'],
            ['id' => 'c', 'nis' => '0012', 'waktu' => 'kemarin'],
        ]);

        $this->assertStringContainsString('Jam di perangkat scan tidak cocok', $hasil[0]['pesan']);
        $this->assertStringContainsString('Waktu scan tidak terbaca', $hasil[1]['pesan']);
        $this->assertStringContainsString('Waktu scan tidak terbaca', $hasil[2]['pesan']);
        $this->seeNumRecords(0, 'presensi', []);

        // A device clock a little ahead of the server is fine.
        $this->assertSame('tercatat', (new Presensi())->scanBanyak([['id' => 'd', 'nis' => '0012', 'waktu' => self::HARI . ' 06:34:00']])[0]['hasil']);
    }

    public function testBatchFromEarlierDayFollowsThatDay(): void
    {
        $this->db->table('libur')->insert(['tanggal' => '2026-10-12', 'keterangan' => 'Rapat guru']);
        Time::setTestNow(self::HARI . ' 06:30:00', 'Asia/Jakarta');
        $hasil = (new Presensi())->scanBanyak([
            ['id' => 'a', 'nis' => '0012', 'waktu' => '2026-10-12 06:50:00'],
            ['id' => 'b', 'nis' => '0012', 'waktu' => '2026-10-10 07:15:00'],
        ]);

        $this->assertStringContainsString('Rapat guru', $hasil[0]['pesan']);
        $this->assertSame('terlambat', $hasil[1]['status']);
        $this->seeInDatabase('presensi', ['tanggal' => '2026-10-10', 'status' => 'terlambat', 'jam_scan' => '07:15:00']);
    }

    public function testDataScanHasTodayRulesStudentsAndRecords(): void
    {
        $this->scanPada('06:45:10');
        Time::setTestNow(self::HARI . ' 06:50:00.250', 'Asia/Jakarta');

        $data = (new Presensi())->dataScan();

        $this->assertSame(Time::parse(self::HARI . ' 06:50:00', 'Asia/Jakarta')->getTimestamp() * 1000 + 250, $data['jam']);
        $this->assertSame(self::HARI, $data['tanggal']);
        $this->assertNull($data['libur']);
        $this->assertSame('08.00', $data['labelTutup']);
        $this->assertSame(['0012' => ['Budi', '7A']], $data['siswa']);
        $this->assertSame(['0012' => ['hadir', '06.45']], $data['tercatat']);
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
