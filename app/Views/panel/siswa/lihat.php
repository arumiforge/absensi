<?php
/**
 * Student profile, tab "Profil" (docs/09 HAL-MD-06, docs/04 FS-MD-04 item
 * 6). Buttons by right; pages of later phases (presensi manual, izin)
 * get their buttons when their routes exist. The photo button follows
 * HA-MD-07 with its scope (docs/04 FS-MD-07).
 *
 * @var array<string, mixed>                                      $siswa
 * @var string                                                    $status     siswa.status code today
 * @var array<string, mixed>|null                                 $rombel     Today's rombel
 * @var list<array<string, mixed>>                                $masaAktif  Newest first
 * @var bool                                                      $terbuka    Has an open active period
 * @var list<array<string, mixed>>                                $penempatan Newest first
 * @var list<array<string, mixed>>                                $atribut    Active attributes with `nilai`
 * @var array<string, mixed>|null                                 $akunSiswa  The account, for HA-AKN-06 holders
 * @var array{jenis: string, sampai: \CodeIgniter\I18n\Time}|null $kunci      Login lock, for HA-AKN-04 holders
 * @var array{ubah: bool, wa: bool, log: bool, reset: bool}       $hak        reset: HA-AKN-04 and the account is not nonaktif
 */
$label  = config('Label')->codes;
$ikon   = static fn (string $name): string => view('komponen/ikon', ['name' => $name], ['saveData' => false]);
$kosong = '<span class="text-body-secondary">Belum diisi</span>';
$teks   = static fn (?string $v): string => $v === null || $v === '' ? $kosong : esc($v);
$id     = (int) $siswa['id'];
$adaFoto = ($siswa['foto_file'] ?? '') !== '';
// HA-MD-07: admin for all students, wali kelas for their rombel (FS-MD-07).
$bolehFoto = service('akunAktif')->boleh('HA-MD-07', ['siswa_id' => $id, 'rombel_id' => $rombel === null ? null : (int) $rombel['id']]);
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', [
    'title'   => $siswa['nama'],
    'context' => 'NISN ' . $siswa['nisn'] . ' · ' . ($rombel === null ? 'Tanpa kelas hari ini' : 'Kelas ' . $rombel['nama']) . ' · ' . $label['siswa.status'][$status],
], ['saveData' => false]) ?>

<?= view('panel/siswa/_tab', ['id' => $id, 'aktif' => 'profil', 'log' => $hak['log']], ['saveData' => false]) ?>

<?php if ($kunci !== null): ?>
<div class="alert alert-warning d-flex flex-wrap align-items-center gap-2" role="status">
    <?= $ikon('triangle-alert') ?>
    <span class="me-auto">Login akun siswa ini sedang dikunci karena terlalu banyak percobaan gagal, sampai <?= esc(format_datetime($kunci['sampai'])) ?>.</span>
    <form method="post" action="<?= esc(url_to('panel.akun_siswa.buka_kunci', $id), 'attr') ?>">
        <?= csrf_field() ?>
        <button class="btn btn-outline-primary" type="submit"><?= $ikon('lock-open') ?> Buka kunci login</button>
    </form>
</div>
<?php endif ?>

<div class="d-flex flex-wrap gap-2 mb-3">
<?php if ($hak['ubah']): ?>
    <a class="btn btn-primary" href="<?= esc(url_to('panel.siswa.ubah', $id), 'attr') ?>"><?= $ikon('pencil') ?> Ubah data</a>
<?php endif ?>
<?php if ($hak['wa']): ?>
    <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.siswa_wa.ubah', $id), 'attr') ?>"><?= $ikon('pencil') ?> Ubah nomor WA</a>
<?php endif ?>
<?php if ($bolehFoto): ?>
    <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.siswa_foto.ubah', $id), 'attr') ?>"><?= $ikon('camera') ?> <?= $adaFoto ? 'Ganti foto' : 'Unggah foto' ?></a>
<?php endif ?>
<?php if ($hak['ubah'] && $terbuka): ?>
    <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.penempatan.tambah', $id), 'attr') ?>"><?= $ikon('users') ?> Pindah kelas</a>
<?php endif ?>
<?php if ($hak['reset']): ?>
    <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.akun_siswa.form_reset_password', $id), 'attr') ?>"><?= $ikon('key-round') ?> Reset password</a>
<?php endif ?>
<?php if ($hak['ubah'] && $terbuka): ?>
    <a class="btn btn-outline-danger" href="<?= esc(url_to('panel.siswa.form_nonaktifkan', $id), 'attr') ?>"><?= $ikon('circle-x') ?> Nonaktifkan</a>
<?php elseif ($hak['ubah']): ?>
    <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.siswa.form_aktifkan', $id), 'attr') ?>"><?= $ikon('circle-check') ?> Aktifkan kembali</a>
<?php endif ?>
</div>

<div class="card card-body mb-3">
    <div class="d-flex flex-wrap gap-3">
        <?= view('komponen/foto_siswa', ['name' => $siswa['nama'], 'src' => $adaFoto ? url_to('panel.siswa_foto.index', $id) : null, 'size' => 'profil', 'decorative' => false], ['saveData' => false]) ?>
        <dl class="row mb-0 flex-grow-1">
            <dt class="col-sm-4">NISN</dt><dd class="col-sm-8"><?= esc($siswa['nisn']) ?></dd>
            <dt class="col-sm-4">NIS</dt><dd class="col-sm-8"><?= $teks($siswa['nis']) ?></dd>
            <dt class="col-sm-4">Nama lengkap</dt><dd class="col-sm-8"><?= esc($siswa['nama']) ?></dd>
            <dt class="col-sm-4">Jenis kelamin</dt><dd class="col-sm-8"><?= $siswa['jenis_kelamin'] === null ? $kosong : esc($label['siswa.jenis_kelamin'][$siswa['jenis_kelamin']]) ?></dd>
            <dt class="col-sm-4">Tanggal lahir</dt><dd class="col-sm-8"><?= $siswa['tanggal_lahir'] === null ? $kosong : esc(format_date($siswa['tanggal_lahir'])) ?></dd>
            <dt class="col-sm-4">Alamat rumah</dt><dd class="col-sm-8"><?= $siswa['alamat'] === null ? $kosong : nl2br(esc($siswa['alamat'])) ?></dd>
            <dt class="col-sm-4">Nama orang tua/wali</dt><dd class="col-sm-8"><?= $teks($siswa['nama_ortu']) ?></dd>
            <dt class="col-sm-4">Nomor WA orang tua/wali</dt><dd class="col-sm-8"><?= $siswa['wa_ortu'] === null ? '<span class="badge text-bg-warning">Tanpa nomor WA</span>' : esc(format_wa($siswa['wa_ortu'])) ?></dd>
            <dt class="col-sm-4">Kelas hari ini</dt><dd class="col-sm-8"><?= $rombel === null ? $kosong : esc($rombel['nama']) ?></dd>
            <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><?= esc($label['siswa.status'][$status]) ?></dd>
<?php foreach ($atribut as $a): ?>
            <dt class="col-sm-4"><?= esc($a['label']) ?></dt><dd class="col-sm-8"><?= $a['nilai'] === null ? $kosong : esc($a['tipe'] === 'tanggal' ? format_date($a['nilai']) : ($a['tipe'] === 'angka' ? format_number($a['nilai'], strlen(strrchr($a['nilai'], '.') ?: '.') - 1) : $a['nilai'])) ?></dd>
<?php endforeach ?>
        </dl>
    </div>
</div>

<?php if ($akunSiswa !== null): ?>
<h2 class="h5">Akun siswa</h2>
<div class="card card-body mb-3"><dl class="row mb-0">
    <dt class="col-sm-4">Username</dt><dd class="col-sm-8"><?= esc($akunSiswa['username']) ?></dd>
    <dt class="col-sm-4">Status akun</dt><dd class="col-sm-8"><?= esc($label['akun.status'][$akunSiswa['status']] ?? $akunSiswa['status']) ?></dd>
    <dt class="col-sm-4">Slip terakhir dibuat</dt><dd class="col-sm-8"><?= $akunSiswa['slip_dibuat_at'] === null ? 'Belum pernah' : esc(format_datetime($akunSiswa['slip_dibuat_at'])) ?></dd>
    <dt class="col-sm-4">Login terakhir</dt><dd class="col-sm-8 mb-0"><?= $akunSiswa['login_terakhir_at'] === null ? 'Belum pernah' : esc(format_datetime($akunSiswa['login_terakhir_at'])) ?></dd>
</dl></div>
<?php endif ?>

<h2 class="h5">Riwayat masa aktif</h2>
<div class="table-responsive mb-3"><table class="table align-middle">
    <thead><tr><th scope="col">Mulai aktif</th><th scope="col">Terakhir aktif</th><th scope="col">Keterangan</th></tr></thead>
    <tbody>
<?php foreach ($masaAktif as $p): ?>
        <tr>
            <td><?= esc(format_date($p['tanggal_mulai'])) ?></td>
            <td><?= $p['dibatalkan'] ? '–' : ($p['tanggal_selesai'] === null ? 'Masih aktif' : esc(format_date($p['tanggal_selesai']))) ?></td>
            <td><?php
                $ket = [];
                if ($p['dibatalkan']) {
                    $ket[] = 'Dibatalkan, tidak dihitung';
                }
                if ($p['alasan_nonaktif'] !== null) {
                    $ket[] = $label['masa_aktif.alasan_nonaktif'][$p['alasan_nonaktif']] . ($p['keterangan_nonaktif'] === null ? '' : ': ' . $p['keterangan_nonaktif']);
                }
                echo $ket === [] ? '–' : esc(implode('. ', $ket));
            ?></td>
        </tr>
<?php endforeach ?>
    </tbody>
</table></div>

<h2 class="h5">Riwayat kelas</h2>
<?php if ($penempatan === []): ?>
<p>Belum pernah ditempatkan di kelas.</p>
<?php else: ?>
<div class="table-responsive mb-3"><table class="table align-middle">
    <thead><tr><th scope="col">Kelas</th><th scope="col">Tahun ajaran</th><th scope="col">Mulai</th><th scope="col">Sampai</th></tr></thead>
    <tbody>
<?php foreach ($penempatan as $p): ?>
        <tr>
            <td><?= esc($p['rombel_nama']) ?></td>
            <td><?= esc($p['tahun_ajaran_nama']) ?></td>
            <td><?= esc(format_date($p['tanggal_mulai'])) ?></td>
            <td><?= esc(format_date($p['selesai'])) ?></td>
        </tr>
<?php endforeach ?>
    </tbody>
</table></div>
<?php endif ?>

<a class="btn btn-link" href="<?= esc(url_to('panel.siswa.index'), 'attr') ?>"><?= $ikon('chevron-left') ?> Kembali ke daftar</a>
<?= $this->endSection() ?>
