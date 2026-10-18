<?php

use App\Services\DataContoh;
use App\Services\Presensi;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class DataContohTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        helper('format');
        // Monday morning before the scan opens.
        Time::setTestNow('2026-10-19 06:00:00', 'Asia/Jakarta');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    public function testFillsStudentsHistoryAndHolidays(): void
    {
        $hasil = (new DataContoh())->isi();

        $this->assertSame(200, $hasil['siswa']);
        $this->assertSame(3, $hasil['libur']);
        $this->assertCount(DataContoh::HARI_RIWAYAT, $hasil['hari']);
        $this->seeNumRecords(200, 'siswa', []);
        $this->seeNumRecords($hasil['presensi'], 'presensi', []);

        // 4th school day back from Monday 19 Oct (Saturday is a school day): Wed 14 Oct.
        $this->seeInDatabase('libur', ['tanggal' => '2026-10-14', 'keterangan' => 'Rapat guru (contoh)']);
        $this->seeInDatabase('libur', ['tanggal' => '2026-12-25']);
        $this->seeInDatabase('libur', ['tanggal' => '2027-01-01']);

        // No records on Sundays, holidays, or today.
        foreach (['2026-10-18', '2026-10-11', '2026-10-14', '2026-10-19'] as $tanggal) {
            $this->assertNotContains($tanggal, $hasil['hari']);
            $this->seeNumRecords(0, 'presensi', ['tanggal' => $tanggal]);
        }
        $this->assertSame('2026-10-06', $hasil['hari'][0]);
        $this->assertSame('2026-10-17', end($hasil['hari']));

        // Every day has a mix of statuses, and some students show as alpa in the recap.
        $rekap = (new Presensi())->rekap('2026-10-16');
        $this->assertCount(200, $rekap['baris']);
        $this->assertGreaterThan(100, $rekap['jumlah']['hadir']);
        $this->assertGreaterThan(0, $rekap['jumlah']['terlambat']);
        $this->assertGreaterThan(0, $rekap['jumlah']['alpa']);
        $this->assertSame(0, $rekap['jumlah']['belum']);
    }

    public function testScanTimesFollowTheRules(): void
    {
        (new DataContoh())->isi();

        $this->seeNumRecords(0, 'presensi', ['status' => 'hadir', 'jam_scan >=' => '07:00:00']);
        $this->seeNumRecords(0, 'presensi', ['status' => 'terlambat', 'jam_scan <' => '07:00:00']);
        $this->seeNumRecords(0, 'presensi', ['status' => 'terlambat', 'jam_scan >=' => '08:00:00']);
        $this->seeNumRecords(0, 'presensi', ['status' => 'izin', 'jam_scan !=' => null]);
    }

    public function testSameDataOnEveryRun(): void
    {
        $pertama = (new DataContoh())->isi();
        $catatan = $this->db->table('presensi')->select('siswa_id, tanggal, status, jam_scan')->orderBy('id')->get()->getResultArray();

        (new DataContoh())->kosongkan();
        $kedua = (new DataContoh())->isi();

        $this->assertSame($pertama['presensi'], $kedua['presensi']);
        $ulang = $this->db->table('presensi')->select('s.nis, p.tanggal, p.status, p.jam_scan')->from('presensi p', true)
            ->join('siswa s', 's.id = p.siswa_id')->orderBy('p.id')->get()->getResultArray();
        $this->assertSame(array_column($catatan, 'status'), array_column($ulang, 'status'));
        $this->assertSame(array_column($catatan, 'jam_scan'), array_column($ulang, 'jam_scan'));
    }

    public function testRefusesWhenStudentsExist(): void
    {
        $this->db->table('siswa')->insert(['nis' => '0012', 'nama' => 'Budi', 'kelas' => '7A']);

        $this->expectException(LogicException::class);
        (new DataContoh())->isi();
    }

    public function testEmptyingKeepsTheAdmin(): void
    {
        $this->db->table('admin')->insert(['username' => 'admin', 'password_hash' => 'x']);
        (new DataContoh())->isi();

        $jumlah = (new DataContoh())->kosongkan();

        $this->assertSame(200, $jumlah['siswa']);
        $this->assertSame(3, $jumlah['libur']);
        $this->seeNumRecords(0, 'siswa', []);
        $this->seeNumRecords(0, 'presensi', []);
        $this->seeNumRecords(0, 'libur', []);
        $this->seeNumRecords(1, 'admin', []);
    }
}
