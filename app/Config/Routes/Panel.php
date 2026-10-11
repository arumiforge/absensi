<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Staff and admin panel (docs/09 RT-01, §4.1).
 *
 * @var RouteCollection $routes
 */
$routes->group('panel', ['namespace' => 'App\Controllers\Panel', 'filter' => ['sesi', 'area:staf', 'wajib-ganti']], static function (RouteCollection $routes): void {
    // Temporary home until FASE-08 (docs/15 §2.2, L01-08).
    $routes->get('/', 'Dashboard::index', ['as' => 'panel.dashboard.index']);

    // HAL-AKN-04 (docs/09 §5)
    $routes->get('akun-staf', 'AkunStaf::index', ['as' => 'panel.akun_staf.index']);
    $routes->get('akun-staf/tambah', 'AkunStaf::tambah', ['as' => 'panel.akun_staf.tambah']);
    $routes->post('akun-staf', 'AkunStaf::simpan', ['as' => 'panel.akun_staf.simpan']);
    $routes->get('akun-staf/(:num)', 'AkunStaf::lihat/$1', ['as' => 'panel.akun_staf.lihat']);
    $routes->get('akun-staf/(:num)/ubah', 'AkunStaf::ubah/$1', ['as' => 'panel.akun_staf.ubah']);
    $routes->patch('akun-staf/(:num)', 'AkunStaf::perbarui/$1', ['as' => 'panel.akun_staf.perbarui']);
    $routes->get('akun-staf/(:num)/nonaktifkan', 'AkunStaf::formNonaktifkan/$1', ['as' => 'panel.akun_staf.form_nonaktifkan']);
    $routes->post('akun-staf/(:num)/nonaktifkan', 'AkunStaf::nonaktifkan/$1', ['as' => 'panel.akun_staf.nonaktifkan']);
    $routes->post('akun-staf/(:num)/aktifkan', 'AkunStaf::aktifkan/$1', ['as' => 'panel.akun_staf.aktifkan']);
    $routes->get('akun-staf/(:num)/reset-password', 'AkunStaf::formResetPassword/$1', ['as' => 'panel.akun_staf.form_reset_password']);
    $routes->post('akun-staf/(:num)/reset-password', 'AkunStaf::resetPassword/$1', ['as' => 'panel.akun_staf.reset_password']);
    $routes->post('akun-staf/(:num)/buka-kunci', 'AkunStaf::bukaKunci/$1', ['as' => 'panel.akun_staf.buka_kunci']);

    // HAL-AKN-05, HAL-AKN-06
    $routes->get('akun-siswa', 'AkunSiswa::index', ['as' => 'panel.akun_siswa.index']);
    $routes->get('akun-siswa/slip', 'AkunSiswa::formSlip', ['as' => 'panel.akun_siswa.form_slip']);
    $routes->post('akun-siswa/slip', 'AkunSiswa::slip', ['as' => 'panel.akun_siswa.slip']);
    $routes->get('siswa/(:num)/reset-password', 'AkunSiswa::formResetPassword/$1', ['as' => 'panel.akun_siswa.form_reset_password']);
    $routes->post('siswa/(:num)/reset-password', 'AkunSiswa::resetPassword/$1', ['as' => 'panel.akun_siswa.reset_password']);
    $routes->post('siswa/(:num)/buka-kunci', 'AkunSiswa::bukaKunci/$1', ['as' => 'panel.akun_siswa.buka_kunci']);

    // HAL-MD-01
    $routes->get('sekolah', 'Sekolah::index', ['as' => 'panel.sekolah.index']);
    $routes->patch('sekolah', 'Sekolah::perbarui', ['as' => 'panel.sekolah.perbarui']);

    // HAL-MD-02
    $routes->get('tahun-ajaran', 'TahunAjaran::index', ['as' => 'panel.tahun_ajaran.index']);
    $routes->get('tahun-ajaran/tambah', 'TahunAjaran::tambah', ['as' => 'panel.tahun_ajaran.tambah']);
    $routes->post('tahun-ajaran', 'TahunAjaran::simpan', ['as' => 'panel.tahun_ajaran.simpan']);
    $routes->get('tahun-ajaran/(:num)/ubah', 'TahunAjaran::ubah/$1', ['as' => 'panel.tahun_ajaran.ubah']);
    $routes->patch('tahun-ajaran/(:num)', 'TahunAjaran::perbarui/$1', ['as' => 'panel.tahun_ajaran.perbarui']);
    $routes->get('tahun-ajaran/(:num)/aktifkan', 'TahunAjaran::formAktifkan/$1', ['as' => 'panel.tahun_ajaran.form_aktifkan']);
    $routes->post('tahun-ajaran/(:num)/aktifkan', 'TahunAjaran::aktifkan/$1', ['as' => 'panel.tahun_ajaran.aktifkan']);
    $routes->get('tahun-ajaran/(:num)/hapus', 'TahunAjaran::formHapus/$1', ['as' => 'panel.tahun_ajaran.form_hapus']);
    $routes->delete('tahun-ajaran/(:num)', 'TahunAjaran::hapus/$1', ['as' => 'panel.tahun_ajaran.hapus']);

    // HAL-MD-03
    $routes->get('kelas', 'Rombel::index', ['as' => 'panel.rombel.index']);
    $routes->get('kelas/tambah', 'Rombel::tambah', ['as' => 'panel.rombel.tambah']);
    $routes->post('kelas', 'Rombel::simpan', ['as' => 'panel.rombel.simpan']);
    $routes->get('kelas/(:num)/ubah', 'Rombel::ubah/$1', ['as' => 'panel.rombel.ubah']);
    $routes->patch('kelas/(:num)', 'Rombel::perbarui/$1', ['as' => 'panel.rombel.perbarui']);
    $routes->get('kelas/(:num)/hapus', 'Rombel::formHapus/$1', ['as' => 'panel.rombel.form_hapus']);
    $routes->delete('kelas/(:num)', 'Rombel::hapus/$1', ['as' => 'panel.rombel.hapus']);

    // HAL-MD-04 to HAL-MD-08, HAL-MD-10. Fixed words before (:num) routes (docs/09 §6).
    $routes->get('siswa', 'Siswa::index', ['as' => 'panel.siswa.index']);
    $routes->get('siswa/cari', 'Siswa::cari', ['as' => 'panel.siswa.cari']);
    $routes->get('siswa/tambah', 'Siswa::tambah', ['as' => 'panel.siswa.tambah']);
    $routes->post('siswa', 'Siswa::simpan', ['as' => 'panel.siswa.simpan']);
    $routes->get('siswa/(:num)', 'Siswa::lihat/$1', ['as' => 'panel.siswa.lihat']);
    $routes->get('siswa/(:num)/ubah', 'Siswa::ubah/$1', ['as' => 'panel.siswa.ubah']);
    $routes->patch('siswa/(:num)', 'Siswa::perbarui/$1', ['as' => 'panel.siswa.perbarui']);
    $routes->get('siswa/(:num)/wa/ubah', 'SiswaWa::ubah/$1', ['as' => 'panel.siswa_wa.ubah']);
    $routes->put('siswa/(:num)/wa', 'SiswaWa::ganti/$1', ['as' => 'panel.siswa_wa.ganti']);
    $routes->get('siswa/(:num)/nonaktifkan', 'Siswa::formNonaktifkan/$1', ['as' => 'panel.siswa.form_nonaktifkan']);
    $routes->post('siswa/(:num)/nonaktifkan', 'Siswa::nonaktifkan/$1', ['as' => 'panel.siswa.nonaktifkan']);
    $routes->get('siswa/(:num)/aktifkan', 'Siswa::formAktifkan/$1', ['as' => 'panel.siswa.form_aktifkan']);
    $routes->post('siswa/(:num)/aktifkan', 'Siswa::aktifkan/$1', ['as' => 'panel.siswa.aktifkan']);

    // HAL-MD-09
    $routes->get('siswa/(:num)/foto', 'SiswaFoto::index/$1', ['as' => 'panel.siswa_foto.index']);
    $routes->get('siswa/(:num)/foto/ubah', 'SiswaFoto::ubah/$1', ['as' => 'panel.siswa_foto.ubah']);
    $routes->put('siswa/(:num)/foto', 'SiswaFoto::ganti/$1', ['as' => 'panel.siswa_foto.ganti']);

    // HAL-MD-11, HAL-MD-12
    $routes->get('siswa/(:num)/penempatan/tambah', 'Penempatan::tambah/$1', ['as' => 'panel.penempatan.tambah']);
    $routes->post('siswa/(:num)/penempatan', 'Penempatan::simpan/$1', ['as' => 'panel.penempatan.simpan']);
    $routes->get('penempatan', 'Penempatan::index', ['as' => 'panel.penempatan.index']);
    $routes->get('penempatan/kelas', 'Penempatan::kelas', ['as' => 'panel.penempatan.kelas']);
    $routes->post('penempatan/kelas', 'Penempatan::simpanKelas', ['as' => 'panel.penempatan.simpan_kelas']);

    // HAL-MD-16
    $routes->get('atribut-siswa', 'AtributSiswa::index', ['as' => 'panel.atribut_siswa.index']);
    $routes->get('atribut-siswa/tambah', 'AtributSiswa::tambah', ['as' => 'panel.atribut_siswa.tambah']);
    $routes->post('atribut-siswa', 'AtributSiswa::simpan', ['as' => 'panel.atribut_siswa.simpan']);
    $routes->get('atribut-siswa/(:num)/ubah', 'AtributSiswa::ubah/$1', ['as' => 'panel.atribut_siswa.ubah']);
    $routes->patch('atribut-siswa/(:num)', 'AtributSiswa::perbarui/$1', ['as' => 'panel.atribut_siswa.perbarui']);
    $routes->post('atribut-siswa/(:num)/sembunyikan', 'AtributSiswa::sembunyikan/$1', ['as' => 'panel.atribut_siswa.sembunyikan']);
    $routes->post('atribut-siswa/(:num)/tampilkan', 'AtributSiswa::tampilkan/$1', ['as' => 'panel.atribut_siswa.tampilkan']);
    $routes->get('atribut-siswa/(:num)/hapus', 'AtributSiswa::formHapus/$1', ['as' => 'panel.atribut_siswa.form_hapus']);
    $routes->delete('atribut-siswa/(:num)', 'AtributSiswa::hapus/$1', ['as' => 'panel.atribut_siswa.hapus']);

    // HAL-MD-17
    $routes->get('siswa/(:num)/log', 'LogDataSiswa::index/$1', ['as' => 'panel.log_data_siswa.index']);

    // HAL-AKN-07
    $routes->get('sistem', 'Sistem::index', ['as' => 'panel.sistem.index']);

    // HAL-AKN-09
    $routes->get('log-aktivitas', 'LogAktivitas::index', ['as' => 'panel.log_aktivitas.index']);
    $routes->get('log-aktivitas/(:num)', 'LogAktivitas::lihat/$1', ['as' => 'panel.log_aktivitas.lihat']);
});
