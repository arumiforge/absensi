<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->addRedirect('/', 'login');

$routes->get('login', 'Login::index', ['as' => 'login']);
$routes->post('login', 'Login::masuk', ['as' => 'login.masuk']);
$routes->post('logout', 'Login::keluar', ['as' => 'logout', 'filter' => 'sesi']);

$routes->group('panel', ['namespace' => 'App\Controllers\Panel', 'filter' => 'sesi'], static function (RouteCollection $routes): void {
    $routes->addRedirect('/', 'panel/rekap');

    $routes->get('scan', 'Scan::index', ['as' => 'scan']);
    $routes->post('scan', 'Scan::simpan', ['as' => 'scan.simpan']);

    $routes->get('rekap', 'Rekap::index', ['as' => 'rekap']);
    $routes->post('rekap', 'Rekap::ubah', ['as' => 'rekap.ubah']);
    $routes->get('rekap/excel', 'Rekap::excel', ['as' => 'rekap.excel']);
    $routes->get('rekap/pdf', 'Rekap::pdf', ['as' => 'rekap.pdf']);

    $routes->get('siswa', 'Siswa::index', ['as' => 'siswa']);
    $routes->get('siswa/tambah', 'Siswa::tambah', ['as' => 'siswa.tambah']);
    $routes->post('siswa', 'Siswa::simpan', ['as' => 'siswa.simpan']);
    $routes->get('siswa/(:num)/ubah', 'Siswa::ubah/$1', ['as' => 'siswa.ubah']);
    $routes->post('siswa/(:num)', 'Siswa::perbarui/$1', ['as' => 'siswa.perbarui']);
    $routes->post('siswa/(:num)/hapus', 'Siswa::hapus/$1', ['as' => 'siswa.hapus']);
    $routes->get('siswa/import', 'Siswa::import', ['as' => 'siswa.import']);
    $routes->post('siswa/import', 'Siswa::prosesImport', ['as' => 'siswa.import.proses']);

    $routes->get('libur', 'Libur::index', ['as' => 'libur']);
    $routes->post('libur', 'Libur::simpan', ['as' => 'libur.simpan']);
    $routes->post('libur/(:segment)/hapus', 'Libur::hapus/$1', ['as' => 'libur.hapus']);

    $routes->get('password', 'Password::index', ['as' => 'password']);
    $routes->post('password', 'Password::ganti', ['as' => 'password.ganti']);
});
