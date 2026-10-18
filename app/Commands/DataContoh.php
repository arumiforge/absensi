<?php

namespace App\Commands;

use App\Models\AdminModel;
use App\Services\DataContoh as Layanan;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DataContoh extends BaseCommand
{
    protected $group       = 'Spensada';
    protected $name        = 'data:contoh';
    protected $description = 'Mengisi database kosong dengan 200 siswa contoh, riwayat presensi, dan libur untuk mencoba aplikasi.';
    protected $usage       = 'data:contoh [--kosongkan]';
    protected $options     = ['--kosongkan' => 'Menghapus semua siswa, presensi, dan libur. Akun admin tetap.'];

    public function run(array $params): int
    {
        $layanan = new Layanan();

        if (array_key_exists('kosongkan', $params)) {
            CLI::write('Semua siswa, presensi, dan libur akan dihapus. Akun admin tetap.', 'yellow');
            if (CLI::prompt('Lanjutkan?', ['y', 'n']) !== 'y') {
                CLI::write('Dibatalkan. Tidak ada data yang dihapus.');

                return EXIT_SUCCESS;
            }
            $jumlah = $layanan->kosongkan();
            CLI::write("Selesai: {$jumlah['siswa']} siswa, {$jumlah['presensi']} catatan presensi, dan {$jumlah['libur']} libur dihapus.", 'green');

            return EXIT_SUCCESS;
        }

        $ada = db_connect()->table('siswa')->countAllResults();
        if ($ada > 0) {
            CLI::error("Sudah ada {$ada} siswa. Data contoh hanya diisi ke database tanpa siswa.");
            CLI::write('Untuk mengosongkan data lebih dulu: php spark data:contoh --kosongkan');

            return EXIT_ERROR;
        }

        CLI::write('Database akan diisi 200 siswa contoh, presensi ' . Layanan::HARI_RIWAYAT . ' hari sekolah terakhir, dan beberapa tanggal libur.', 'yellow');
        if (CLI::prompt('Lanjutkan?', ['y', 'n']) !== 'y') {
            CLI::write('Dibatalkan. Tidak ada data yang diisi.');

            return EXIT_SUCCESS;
        }

        $hasil = $layanan->isi();
        CLI::write("Selesai: {$hasil['siswa']} siswa, {$hasil['presensi']} catatan presensi, dan {$hasil['libur']} libur.", 'green');
        CLI::write('Riwayat presensi: ' . format_date($hasil['hari'][0]) . ' sampai ' . format_date(end($hasil['hari'])) . '. Hari ini masih kosong, siap dicoba di halaman Scan QR.');
        CLI::write('Kartu QR contoh untuk dicetak atau dibuka di HP: contoh/kartu-qr-contoh.pdf');

        if (model(AdminModel::class)->first() === null) {
            CLI::newLine();
            $this->call('admin:password', ['username' => 'admin']);
        }

        CLI::newLine();
        CLI::write('Sebelum dipakai sungguhan, hapus data contoh: php spark data:contoh --kosongkan');

        return EXIT_SUCCESS;
    }
}
