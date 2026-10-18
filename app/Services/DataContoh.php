<?php

namespace App\Services;

use App\Services\Sistem\Jam;
use CodeIgniter\I18n\Time;
use LogicException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

/**
 * Sample data for trying the app before real students are imported:
 * the students in `contoh/siswa-contoh.csv`, attendance for the last
 * school days before today, and a few holidays. Today stays empty so the
 * scan page can be tried with the sample QR cards.
 *
 * The data is the same on every run: random choices use a fixed seed.
 */
class DataContoh
{
    public const BERKAS = ROOTPATH . 'contoh/siswa-contoh.csv';

    /**
     * School days before today that get attendance records.
     */
    public const HARI_RIWAYAT = 10;

    private const SEED = 2026;

    /**
     * Status weights out of 100; the rest get no record and show as alpa.
     */
    private const PELUANG = ['hadir' => 78, 'terlambat' => 10, 'izin' => 3, 'sakit' => 3, 'dispensasi' => 1];

    private const KETERANGAN = [
        'izin'       => ['Acara keluarga', 'Mengantar orang tua ke dokter', 'Urusan keluarga'],
        'sakit'      => ['Demam', 'Batuk dan pilek', 'Sakit perut'],
        'dispensasi' => ['Lomba pramuka', 'Latihan paskibra', 'Lomba MAPSI'],
    ];

    public function __construct(private readonly Jam $jam = new Jam())
    {
    }

    /**
     * Fills an empty database with the sample data.
     *
     * @return array{siswa: int, presensi: int, libur: int, hari: list<string>}
     */
    public function isi(string $berkas = self::BERKAS): array
    {
        if (db_connect()->table('siswa')->countAllResults() > 0) {
            throw new LogicException('Sample data only goes into an empty siswa table.');
        }

        $rows  = IOFactory::createReader('Csv')->load($berkas)->getActiveSheet()->toArray(null, true, false, false);
        $galat = [];
        $hasil = (new ImportSiswa())->jalankan($rows, $galat);
        if ($galat !== []) {
            throw new RuntimeException('Sample student file rejected: ' . implode(' ', $galat));
        }

        mt_srand(self::SEED);
        $now   = $this->jam->now();
        $libur = $this->isiLibur($now);
        $hari  = $this->hariRiwayat($now);

        // Students must exist before the first day, or the recap leaves them out.
        $dibuat = $now->subDays(30)->toDateTimeString();
        db_connect()->table('siswa')->update(['created_at' => $dibuat, 'updated_at' => $dibuat]);

        $siswa    = array_column(db_connect()->table('siswa')->select('id')->orderBy('id')->get()->getResultArray(), 'id');
        $presensi = [];
        foreach ($hari as $tanggal) {
            foreach ($siswa as $id) {
                $baris = $this->presensi((int) $id, $tanggal);
                if ($baris !== null) {
                    $presensi[] = $baris;
                }
            }
        }
        if ($presensi !== []) {
            db_connect()->table('presensi')->insertBatch($presensi, null, 500);
        }

        return ['siswa' => $hasil['baru'], 'presensi' => count($presensi), 'libur' => $libur, 'hari' => $hari];
    }

    /**
     * Deletes every student (with their attendance) and every holiday.
     * The admin account stays.
     *
     * @return array{siswa: int, presensi: int, libur: int}
     */
    public function kosongkan(): array
    {
        $db     = db_connect();
        $jumlah = [
            'siswa'    => $db->table('siswa')->countAllResults(),
            'presensi' => $db->table('presensi')->countAllResults(),
            'libur'    => $db->table('libur')->countAllResults(),
        ];

        $db->transStart();
        $db->table('presensi')->emptyTable();
        $db->table('siswa')->emptyTable();
        $db->table('libur')->emptyTable();
        $db->transComplete();

        return $jumlah;
    }

    /**
     * One school day in the history window, plus the next Christmas and
     * New Year's Day. Returns how many were added.
     */
    private function isiLibur(Time $now): int
    {
        $tahun   = (int) $now->format('Y');
        $natal   = $now->format('m-d') > '12-25' ? $tahun + 1 : $tahun;
        $daftar  = [
            $this->hariSekolahLalu($now, 4) => 'Rapat guru (contoh)',
            "{$natal}-12-25"                => 'Hari Raya Natal',
            ($tahun + 1) . '-01-01'          => 'Tahun Baru Masehi',
        ];

        $jumlah = 0;
        foreach ($daftar as $tanggal => $keterangan) {
            db_connect()->table('libur')->ignore(true)->insert(['tanggal' => $tanggal, 'keterangan' => $keterangan]);
            $jumlah += db_connect()->affectedRows();
        }

        return $jumlah;
    }

    /**
     * The n-th day before today that is not a Sunday.
     */
    private function hariSekolahLalu(Time $now, int $n): string
    {
        $hari = $now;
        do {
            $hari = $hari->subDays(1);
            $n -= $hari->format('w') === '0' ? 0 : 1;
        } while ($n > 0);

        return $hari->toDateString();
    }

    /**
     * The last school days before today, oldest first.
     *
     * @return list<string>
     */
    private function hariRiwayat(Time $now): array
    {
        $presensi = new Presensi($this->jam);
        $hari     = [];
        $tanggal  = $now;

        while (count($hari) < self::HARI_RIWAYAT) {
            $tanggal = $tanggal->subDays(1);
            if ($presensi->libur($tanggal->toDateString()) === null) {
                $hari[] = $tanggal->toDateString();
            }
        }

        return array_reverse($hari);
    }

    /**
     * @return array<string, mixed>|null Null when the student has no record that day (alpa).
     */
    private function presensi(int $siswaId, string $tanggal): ?array
    {
        $angka  = mt_rand(1, 100);
        $status = null;
        foreach (self::PELUANG as $kode => $peluang) {
            if ($angka <= $peluang) {
                $status = $kode;
                break;
            }
            $angka -= $peluang;
        }
        if ($status === null) {
            return null;
        }

        $jam = match ($status) {
            'hadir'     => self::jamAcak('06:15:00', '06:59:59'),
            'terlambat' => self::jamAcak('07:00:00', '07:40:00'),
            default     => null,
        };
        $keterangan = isset(self::KETERANGAN[$status]) ? self::KETERANGAN[$status][mt_rand(0, 2)] : null;
        $dibuat     = $tanggal . ' ' . ($jam ?? '08:30:00');

        return [
            'siswa_id'   => $siswaId,
            'tanggal'    => $tanggal,
            'status'     => $status,
            'jam_scan'   => $jam,
            'keterangan' => $keterangan,
            'created_at' => $dibuat,
            'updated_at' => $dibuat,
        ];
    }

    private static function jamAcak(string $dari, string $sampai): string
    {
        $detik = static fn (string $jam): int => (int) substr($jam, 0, 2) * 3600 + (int) substr($jam, 3, 2) * 60 + (int) substr($jam, 6, 2);
        $nilai = mt_rand($detik($dari), $detik($sampai));

        return sprintf('%02d:%02d:%02d', intdiv($nilai, 3600), intdiv($nilai % 3600, 60), $nilai % 60);
    }
}
