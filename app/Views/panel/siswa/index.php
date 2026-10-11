<?php
/**
 * Student list and search (docs/09 HAL-MD-04, RT-14, docs/08 UI-26, UI-28).
 * With JavaScript, cari-siswa.js shows live results from the search
 * fragment (docs/10 EP-MD-01) above the table.
 *
 * @var array<string, mixed>       $saringan Valid filters in use
 * @var string                     $cari     The `cari` text as typed
 * @var bool                       $abaikan  Some filter value was not recognised
 * @var list<array<string, mixed>> $kelas    Rombel of the active year in scope
 * @var list<array<string, mixed>> $rows
 * @var int                        $total
 * @var int                        $page
 * @var bool                       $kosong   No student in scope at all (E6)
 * @var bool                       $admin    Holds HA-MD-03
 */
$label  = config('Label')->codes['siswa.status'];
$ikon   = static fn (string $name): string => view('komponen/ikon', ['name' => $name], ['saveData' => false]);
$status = $saringan['status'] ?? 'aktif';
$centang = static fn (string $tanda): string => empty($saringan[$tanda]) ? '' : ' checked';
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', [
    'title'   => 'Data siswa',
    'actions' => $admin ? '<a class="btn btn-primary" href="' . esc(url_to('panel.siswa.tambah'), 'attr') . '">' . $ikon('user') . ' Tambah siswa</a>' : null,
], ['saveData' => false]) ?>

<?php if ($abaikan): ?>
<div class="alert alert-warning" role="status">Sebagian saringan tidak dikenali, sehingga diabaikan.</div>
<?php endif ?>

<form class="row g-2 align-items-end mb-3" method="get" action="<?= esc(url_to('panel.siswa.index'), 'attr') ?>">
    <div class="col-12 col-md-5">
        <label class="form-label" for="cari">Nama atau NISN</label>
        <input class="form-control" type="search" id="cari" name="cari" maxlength="50" autocomplete="off" value="<?= esc($cari, 'attr') ?>"
            aria-describedby="cari-bantuan" aria-controls="hasil-cari"
            data-cari-siswa="<?= esc(url_to('panel.siswa.cari'), 'attr') ?>" data-cari-untuk="profil" data-cari-hasil="hasil-cari">
<?php if ($cari !== '' && mb_strlen($cari) < 2): ?>
        <div class="form-text text-danger" id="cari-bantuan">Tulis paling sedikit 2 huruf nama atau angka NISN.</div>
<?php else: ?>
        <div class="form-text" id="cari-bantuan">Paling sedikit 2 huruf nama atau angka NISN.</div>
<?php endif ?>
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label" for="kelas">Kelas</label>
        <select class="form-select" id="kelas" name="kelas">
            <option value="">Semua kelas</option>
<?php foreach ($kelas as $r): ?>
            <option value="<?= esc((string) $r['id'], 'attr') ?>"<?= ($saringan['kelas'] ?? null) === (int) $r['id'] ? ' selected' : '' ?>><?= esc($r['nama']) ?></option>
<?php endforeach ?>
        </select>
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status">
<?php foreach ($label as $kode => $nama): ?>
            <option value="<?= esc($kode, 'attr') ?>"<?= $status === $kode ? ' selected' : '' ?>><?= esc($nama) ?></option>
<?php endforeach ?>
            <option value="semua"<?= $status === 'semua' ? ' selected' : '' ?>>Semua status</option>
        </select>
    </div>
    <div class="col-12 col-md-3">
        <button class="btn btn-outline-primary w-100" type="submit"><?= $ikon('search') ?> Cari</button>
    </div>
    <div class="col-12 d-flex flex-wrap gap-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="tanpa_wa" name="tanpa_wa" value="1"<?= $centang('tanpa_wa') ?>>
            <label class="form-check-label" for="tanpa_wa">Tanpa nomor WA</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="tanpa_foto" name="tanpa_foto" value="1"<?= $centang('tanpa_foto') ?>>
            <label class="form-check-label" for="tanpa_foto">Tanpa foto</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="tanpa_kelas" name="tanpa_kelas" value="1"<?= $centang('tanpa_kelas') ?>>
            <label class="form-check-label" for="tanpa_kelas">Aktif tanpa kelas hari ini</label>
        </div>
    </div>
</form>

<div id="hasil-cari" class="mb-3" aria-live="polite"></div>

<?php ob_start() ?>
<table class="table align-middle">
    <thead>
        <tr><th scope="col"><span class="visually-hidden">Foto</span></th><th scope="col">Nama</th><th scope="col">NISN</th><th scope="col">Kelas</th><th scope="col">Status</th><th scope="col">Tanda</th></tr>
    </thead>
    <tbody>
<?php foreach ($rows as $row): ?>
        <tr>
            <td><?= view('komponen/foto_siswa', ['name' => $row['nama'], 'src' => $row['foto_file'] === null ? null : url_to('panel.siswa_foto.index', $row['id']) . '?ukuran=kecil'], ['saveData' => false]) ?></td>
            <td><a href="<?= esc(url_to('panel.siswa.lihat', $row['id']), 'attr') ?>"><?= esc($row['nama']) ?></a></td>
            <td><?= esc($row['nisn']) ?></td>
            <td><?= $row['rombel_nama'] === null ? '–' : esc($row['rombel_nama']) ?></td>
            <td><?= esc($label[$row['status']]) ?></td>
            <td>
<?php if ($row['wa_ortu'] === null): ?>
                <span class="badge text-bg-warning">Tanpa nomor WA</span>
<?php endif ?>
<?php if ($row['foto_file'] === null): ?>
                <span class="badge text-bg-secondary">Tanpa foto</span>
<?php endif ?>
            </td>
        </tr>
<?php endforeach ?>
    </tbody>
</table>
<?php $table = ob_get_clean() ?>
<?= view('komponen/halaman_daftar', [
    'total' => $total,
    'page'  => $page,
    'table' => $table,
    'empty' => $kosong ? 'Belum ada siswa.' : 'Tidak ada siswa yang cocok dengan saringan.',
], ['saveData' => false]) ?>
<?php if ($kosong && $admin): ?>
<p><a class="btn btn-outline-primary" href="<?= esc(url_to('panel.siswa.tambah'), 'attr') ?>"><?= $ikon('user') ?> Tambah siswa</a></p>
<?php endif ?>
<script type="module" src="<?= esc(base_url('aset/js/cari-siswa.js') . '?v=' . config('Spensada')->versi, 'attr') ?>"></script>
<?= $this->endSection() ?>
