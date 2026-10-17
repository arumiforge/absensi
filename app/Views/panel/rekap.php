<?php
use App\Services\Presensi;

$warna  = ['hadir' => 'success', 'terlambat' => 'warning', 'izin' => 'info', 'sakit' => 'info', 'dispensasi' => 'primary', 'alpa' => 'danger', 'belum' => 'secondary'];
$unduh  = '?' . http_build_query(['tanggal' => $tanggal, 'kelas' => $kelas]);
?>
<?= $this->extend('layout/panel') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="h3 mb-0">Rekap harian</h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-success" href="<?= url_to('rekap.excel') . esc($unduh) ?>">Unduh Excel</a>
        <a class="btn btn-outline-danger" href="<?= url_to('rekap.pdf') . esc($unduh) ?>">Unduh PDF</a>
    </div>
</div>

<form method="get" action="<?= url_to('rekap') ?>" class="row g-2 align-items-end mb-3">
    <div class="col-sm-auto">
        <label for="tanggal" class="form-label">Tanggal</label>
        <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= esc($tanggal) ?>">
    </div>
    <div class="col-sm-auto">
        <label for="kelas" class="form-label">Kelas</label>
        <select class="form-select" id="kelas" name="kelas">
            <option value="">Semua kelas</option>
<?php foreach ($daftarKelas as $item): ?>
            <option value="<?= esc($item) ?>"<?= $item === $kelas ? ' selected' : '' ?>><?= esc($item) ?></option>
<?php endforeach ?>
        </select>
    </div>
    <div class="col-sm-auto"><button type="submit" class="btn btn-primary">Tampilkan</button></div>
</form>

<p class="fw-semibold mb-2"><?= esc(format_date($tanggal, 'full')) ?></p>
<?php if ($rekap['libur'] !== null): ?>
<div class="alert alert-secondary">Libur: <?= esc($rekap['libur']) ?>. Tidak ada siswa yang otomatis Alpa.</div>
<?php endif ?>

<div class="d-flex flex-wrap gap-2 mb-3">
<?php foreach ([...Presensi::STATUS, 'belum' => 'Belum ada data'] as $kode => $label): ?>
    <span class="badge text-bg-<?= $warna[$kode] ?> fs-6 fw-normal"><?= $label ?>: <?= format_number($rekap['jumlah'][$kode]) ?></span>
<?php endforeach ?>
</div>

<?php if ($rekap['baris'] === []): ?>
<p class="text-body-secondary">Belum ada siswa. Tambahkan siswa di menu Siswa.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm table-hover align-middle bg-body">
    <thead>
        <tr><th>No</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Status</th><th>Jam scan</th><th>Ubah status</th></tr>
    </thead>
    <tbody>
<?php foreach ($rekap['baris'] as $i => $siswa): ?>
<?php $status = $siswa['status'] ?? 'belum'; ?>
        <tr id="siswa-<?= $siswa['id'] ?>">
            <td><?= $i + 1 ?></td>
            <td><?= esc($siswa['nis']) ?></td>
            <td><?= esc($siswa['nama']) ?></td>
            <td><?= esc($siswa['kelas']) ?></td>
            <td><span class="badge text-bg-<?= $warna[$status] ?>"><?= Presensi::STATUS[$status] ?? '–' ?></span></td>
            <td><?= esc(format_time($siswa['jam_scan'])) ?></td>
            <td>
                <form method="post" action="<?= url_to('rekap.ubah') ?>" class="d-flex flex-wrap gap-1">
                    <?= csrf_field() ?>
                    <input type="hidden" name="siswa_id" value="<?= $siswa['id'] ?>">
                    <input type="hidden" name="tanggal" value="<?= esc($tanggal) ?>">
                    <input type="hidden" name="kelas" value="<?= esc($kelas) ?>">
                    <select name="status" class="form-select form-select-sm w-auto" aria-label="Status <?= esc($siswa['nama']) ?>">
                        <option value="">Kosongkan</option>
<?php foreach (Presensi::STATUS as $kode => $label): ?>
                        <option value="<?= $kode ?>"<?= $kode === $siswa['status'] ? ' selected' : '' ?>><?= $label ?></option>
<?php endforeach ?>
                    </select>
                    <input type="text" name="keterangan" class="form-control form-control-sm w-auto" maxlength="255" placeholder="Keterangan" value="<?= esc($siswa['keterangan'] ?? '') ?>" aria-label="Keterangan <?= esc($siswa['nama']) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Simpan</button>
                </form>
            </td>
        </tr>
<?php endforeach ?>
    </tbody>
</table>
</div>
<?php endif ?>
<?= $this->endSection() ?>
