<?php

namespace App\Services;

use App\Models\LiburModel;
use App\Models\SiswaModel;
use App\Services\Sistem\Jam;

/**
 * Morning attendance rules: scan before 07:00 is on time, until 08:00 is
 * late, after that no scan is taken and students without a record are
 * absent (alpa). Sundays and holidays have no attendance.
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
     * @return array{hasil: 'tercatat'|'sudah'|'galat', pesan: string, nama?: string, kelas?: string, status?: string, jam?: string}
     */
    public function scan(string $nis): array
    {
        $siswa = model(SiswaModel::class)->where('nis', trim($nis))->first();
        if ($siswa === null) {
            return ['hasil' => 'galat', 'pesan' => 'Kode QR tidak dikenal. Pastikan kartu milik siswa yang terdaftar.'];
        }

        $now     = $this->jam->now();
        $tanggal = $now->toDateString();

        if (($libur = $this->libur($tanggal)) !== null) {
            return ['hasil' => 'galat', 'pesan' => "Hari ini libur ({$libur}). Presensi tidak dicatat."];
        }

        $tabel = db_connect()->table('presensi');
        $ada   = $tabel->where(['siswa_id' => $siswa['id'], 'tanggal' => $tanggal])->get()->getRowArray();

        if ($ada === null) {
            $status = self::statusScan($now->toTimeString());
            if ($status === null) {
                return ['hasil' => 'galat', 'pesan' => 'Presensi pagi sudah ditutup pukul ' . format_time(self::JAM_TUTUP) . '. Minta admin mengubah status bila perlu.'];
            }

            $tabel->ignore(true)->insert([
                'siswa_id'   => $siswa['id'],
                'tanggal'    => $tanggal,
                'status'     => $status,
                'jam_scan'   => $now->toTimeString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);

            // Zero rows: the same card was scanned twice at once; the other request won.
            if (db_connect()->affectedRows() === 1) {
                return $this->hasil('tercatat', $siswa, $status, $now->toTimeString());
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

    private function hasil(string $hasil, array $siswa, string $status, ?string $jam): array
    {
        $label = self::STATUS[$status];
        $pesan = $hasil === 'tercatat'
            ? "{$siswa['nama']} tercatat {$label}."
            : "{$siswa['nama']} sudah tercatat {$label}" . ($jam === null ? '' : ' pukul ' . format_time($jam)) . '.';

        return ['hasil' => $hasil, 'pesan' => $pesan, 'nama' => $siswa['nama'], 'kelas' => $siswa['kelas'], 'status' => $status, 'jam' => $jam === null ? '' : format_time($jam)];
    }
}
