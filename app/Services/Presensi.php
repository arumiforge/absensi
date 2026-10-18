<?php

namespace App\Services;

use App\Models\LiburModel;
use App\Models\SiswaModel;
use App\Services\Sistem\Jam;
use CodeIgniter\I18n\Time;

/**
 * Morning attendance rules: scan before 07:00 is on time, until 08:00 is
 * late, after that no scan is taken and students without a record are
 * absent (alpa). Sundays and holidays have no attendance.
 *
 * The scan page is local-first: it judges each scan on the device with
 * dataScan(), keeps it in the browser, and sends batches to scanBanyak()
 * in the background. The server applies the same rules again.
 */
class Presensi
{
    public const STATUS = [
        'hadir'      => 'Hadir',
        'terlambat'  => 'Terlambat',
        'izin'       => 'Izin',
        'sakit'      => 'Sakit',
        'dispensasi' => 'Dispensasi',
        'alpa'       => 'Alpa',
    ];

    public const JAM_MASUK = '07:00:00';
    public const JAM_TUTUP = '08:00:00';

    /**
     * How far a scan time may be ahead of the server clock.
     */
    public const SELISIH_MAKS_DETIK = 300;

    /**
     * @var array<string, string|null> Holiday reason per date, for one batch.
     */
    private array $liburCache = [];

    public function __construct(private readonly Jam $jam = new Jam())
    {
    }

    /**
     * Status for a scan at this time (H:i:s), or null when attendance is closed.
     */
    public static function statusScan(string $jam): ?string
    {
        if ($jam < self::JAM_MASUK) {
            return 'hadir';
        }

        return $jam < self::JAM_TUTUP ? 'terlambat' : null;
    }

    /**
     * Why there is no attendance on this date, or null on a school day.
     */
    public function libur(string $tanggal): ?string
    {
        if (date('w', strtotime($tanggal)) === '0') {
            return 'Hari Minggu';
        }

        return model(LiburModel::class)->find($tanggal)['keterangan'] ?? null;
    }

    /**
     * Everything the scan page needs to judge scans on the device: the
     * server clock, today's rules, all students, and today's records.
     *
     * @return array<string, mixed>
     */
    public function dataScan(): array
    {
        $now     = $this->jam->now();
        $tanggal = $now->toDateString();
        $siswa   = [];
        $catatan = [];

        foreach (model(SiswaModel::class)->select('nis, nama, kelas')->findAll() as $row) {
            $siswa[self::kunci($row['nis'])] = [$row['nama'], $row['kelas']];
        }

        $rows = db_connect()->table('presensi p')->select('s.nis, p.status, p.jam_scan')
            ->join('siswa s', 's.id = p.siswa_id')->where('p.tanggal', $tanggal)->get()->getResultArray();
        foreach ($rows as $row) {
            $catatan[self::kunci($row['nis'])] = [$row['status'], $row['jam_scan'] === null ? '' : format_time($row['jam_scan'])];
        }

        return [
            'jam'        => self::milidetik($now),
            'tanggal'    => $tanggal,
            'libur'      => $this->libur($tanggal),
            'jamMasuk'   => self::JAM_MASUK,
            'jamTutup'   => self::JAM_TUTUP,
            'labelTutup' => format_time(self::JAM_TUTUP),
            'status'     => self::STATUS,
            'siswa'      => $siswa,
            'tercatat'   => $catatan,
        ];
    }

    /**
     * Records a batch of scans sent by the scan page. Each scan carries the
     * time it was made on the device, so the rules use that time, not the
     * time the batch arrives.
     *
     * @param list<array{id: string, nis: string, waktu: string}> $daftar
     *
     * @return list<array<string, string>>
     */
    public function scanBanyak(array $daftar): array
    {
        $nis   = array_values(array_unique(array_map(static fn (array $scan): string => trim($scan['nis']), $daftar)));
        $siswa = [];
        if ($nis !== []) {
            foreach (model(SiswaModel::class)->whereIn('nis', $nis)->findAll() as $row) {
                $siswa[self::kunci($row['nis'])] = $row;
            }
        }

        $hasil = [];
        foreach ($daftar as $scan) {
            $hasil[] = ['id' => $scan['id']] + $this->catat($siswa[self::kunci($scan['nis'])] ?? null, $scan['waktu']);
        }

        return $hasil;
    }

    /**
     * One scan, made now or at the given device time (`YYYY-MM-DD HH:MM:SS`, WIB).
     *
     * @return array{hasil: 'tercatat'|'sudah'|'galat', pesan: string, nama?: string, kelas?: string, status?: string, jam?: string}
     */
    public function scan(string $nis, ?string $waktu = null): array
    {
        return $this->catat(model(SiswaModel::class)->where('nis', trim($nis))->first(), $waktu ?? $this->jam->now()->toDateTimeString());
    }

    /**
     * @return array{hasil: 'tercatat'|'sudah'|'galat', pesan: string, nama?: string, kelas?: string, status?: string, jam?: string}
     */
    private function catat(?array $siswa, string $waktu): array
    {
        if ($siswa === null) {
            return ['hasil' => 'galat', 'pesan' => 'Kode QR tidak dikenal. Pastikan kartu milik siswa yang terdaftar.'];
        }

        $now = $this->jam->now();
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $waktu, $m) !== 1 || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return ['hasil' => 'galat', 'pesan' => 'Waktu scan tidak terbaca. Muat ulang halaman scan, lalu scan lagi.'];
        }
        if (Time::parse($waktu, 'Asia/Jakarta')->getTimestamp() > $now->getTimestamp() + self::SELISIH_MAKS_DETIK) {
            return ['hasil' => 'galat', 'pesan' => 'Jam di perangkat scan tidak cocok dengan jam server. Muat ulang halaman scan, lalu scan lagi.'];
        }

        [$tanggal, $jam] = explode(' ', $waktu);

        if (! array_key_exists($tanggal, $this->liburCache)) {
            $this->liburCache[$tanggal] = $this->libur($tanggal);
        }
        if (($libur = $this->liburCache[$tanggal]) !== null) {
            return ['hasil' => 'galat', 'pesan' => "Hari ini libur ({$libur}). Presensi tidak dicatat."];
        }

        $tabel = db_connect()->table('presensi');
        $ada   = $tabel->where(['siswa_id' => $siswa['id'], 'tanggal' => $tanggal])->get()->getRowArray();

        if ($ada === null) {
            $status = self::statusScan($jam);
            if ($status === null) {
                return ['hasil' => 'galat', 'pesan' => 'Presensi pagi sudah ditutup pukul ' . format_time(self::JAM_TUTUP) . '. Minta admin mengubah status bila perlu.'];
            }

            $tabel->ignore(true)->insert([
                'siswa_id'   => $siswa['id'],
                'tanggal'    => $tanggal,
                'status'     => $status,
                'jam_scan'   => $jam,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);

            // Zero rows: the same card was scanned twice at once; the other request won.
            if (db_connect()->affectedRows() === 1) {
                return $this->hasil('tercatat', $siswa, $status, $jam);
            }

            $ada = $tabel->where(['siswa_id' => $siswa['id'], 'tanggal' => $tanggal])->get()->getRowArray();
        }

        return $this->hasil('sudah', $siswa, $ada['status'], $ada['jam_scan']);
    }

    /**
     * Daily recap: every student who existed on that date, with the status
     * stored for that day, or alpa once attendance has closed.
     *
     * @return array{baris: list<array<string, mixed>>, jumlah: array<string, int>, libur: string|null}
     */
    public function rekap(string $tanggal, ?string $kelas = null): array
    {
        $builder = db_connect()->table('siswa s')
            ->select('s.id, s.nis, s.nama, s.kelas, s.created_at, p.status, p.jam_scan, p.keterangan')
            ->join('presensi p', 'p.siswa_id = s.id AND p.tanggal = ' . db_connect()->escape($tanggal), 'left')
            ->where('DATE(s.created_at) <=', $tanggal)
            ->orderBy('s.kelas')->orderBy('s.nama');

        if ($kelas !== null && $kelas !== '') {
            $builder->where('s.kelas', $kelas);
        }

        $now    = $this->jam->now();
        $libur  = $this->libur($tanggal);
        $tutup  = $libur === null && ($tanggal < $now->toDateString() || ($tanggal === $now->toDateString() && $now->toTimeString() >= self::JAM_TUTUP));
        $jumlah = array_fill_keys([...array_keys(self::STATUS), 'belum'], 0);
        $baris  = $builder->get()->getResultArray();

        foreach ($baris as &$row) {
            // Students added on this very day are not marked absent for it.
            if ($row['status'] === null && $tutup && substr($row['created_at'], 0, 10) < $tanggal) {
                $row['status'] = 'alpa';
            }
            $jumlah[$row['status'] ?? 'belum']++;
        }

        return ['baris' => $baris, 'jumlah' => $jumlah, 'libur' => $libur];
    }

    /**
     * Manual status by the admin. An empty status removes the record. The scan time is kept.
     */
    public function ubah(int $siswaId, string $tanggal, string $status, string $keterangan): void
    {
        $tabel = db_connect()->table('presensi');
        $where = ['siswa_id' => $siswaId, 'tanggal' => $tanggal];

        if ($status === '') {
            $tabel->where($where)->delete();

            return;
        }

        $now  = $this->jam->now()->toDateTimeString();
        $data = ['status' => $status, 'keterangan' => $keterangan === '' ? null : $keterangan, 'updated_at' => $now];

        if ($tabel->where($where)->countAllResults() > 0) {
            $tabel->where($where)->update($data);
        } else {
            $tabel->insert($where + $data + ['created_at' => $now]);
        }
    }

    /**
     * Server time in milliseconds since the epoch, for the scan page clock.
     */
    public static function milidetik(Time $waktu): int
    {
        return $waktu->getTimestamp() * 1000 + (int) $waktu->format('v');
    }

    /**
     * Lookup key for a NIS; MySQL compares NIS without case.
     */
    private static function kunci(string $nis): string
    {
        return mb_strtolower(trim($nis));
    }

    private function hasil(string $hasil, array $siswa, string $status, ?string $jam): array
    {
        $label = self::STATUS[$status];
        $pesan = $hasil === 'tercatat'
            ? "{$siswa['nama']} tercatat {$label}."
            : "{$siswa['nama']} sudah tercatat {$label}" . ($jam === null ? '' : ' pukul ' . format_time($jam)) . '.';

        return ['hasil' => $hasil, 'pesan' => $pesan, 'nama' => $siswa['nama'], 'kelas' => $siswa['kelas'], 'status' => $status, 'jam' => $jam === null ? '' : format_time($jam)];
    }
}
