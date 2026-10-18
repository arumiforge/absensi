<?php
use App\Services\Presensi;

$warna  = ['hadir' => 'success', 'terlambat' => 'warning', 'izin' => 'info', 'sakit' => 'primary', 'dispensasi' => 'dark', 'alpa' => 'danger', 'belum' => 'secondary'];
$label  = [...Presensi::STATUS, 'belum' => 'Belum ada data'];
$unduh  = '?' . http_build_query(['tanggal' => $tanggal, 'kelas' => $kelas]);
// Link to this page with some filters changed; empty filters are left out.
$tautan = static fn (array $ubah): string => url_to('rekap') . '?' . http_build_query(array_filter(
    $ubah + ['tanggal' => $tanggal, 'kelas' => $kelas, 'status' => $status, 'cari' => $cari],
    static fn (string $nilai): bool => $nilai !== '',
));
$total    = array_sum($rekap['jumlah']);
$disaring = $status !== '' || $cari !== '';
// Status filter buttons: label, count, badge color.
$saringan = ['' => ['Semua', $total, 'dark']];
foreach ($label as $kode => $teks) {
    $saringan[$kode] = [$teks, $rekap['jumlah'][$kode], $warna[$kode]];
}
?>
<?= $this->extend('layout/panel') ?>
<?= $this->section('head') ?>
<script src="<?= esc(base_url('aset/js/rekap.js') . '?v=' . config('Spensada')->versi) ?>" defer></script>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="h3 mb-0">Rekap harian</h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-success" href="<?= url_to('rekap.excel') . esc($unduh) ?>">Unduh Excel</a>
        <a class="btn btn-outline-danger" href="<?= url_to('rekap.pdf') . esc($unduh) ?>">Unduh PDF</a>
    </div>
</div>

<form method="get" action="<?= url_to('rekap') ?>" class="row g-2 align-items-end mb-3">
    <div class="col-12 col-md-auto">
        <label for="tanggal" class="form-label">Tanggal</label>
        <div class="input-group">
            <a class="btn btn-outline-secondary" href="<?= esc($tautan(['tanggal' => $kemarin])) ?>" aria-label="Hari sebelumnya" title="Hari sebelumnya">&lsaquo;</a>
            <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= esc($tanggal) ?>">
            <a class="btn btn-outline-secondary" href="<?= esc($tautan(['tanggal' => $besok])) ?>" aria-label="Hari berikutnya" title="Hari berikutnya">&rsaquo;</a>
        </div>
    </div>
    <div class="col-6 col-md-auto">
        <label for="kelas" class="form-label">Kelas</label>
        <select class="form-select" id="kelas" name="kelas">
            <option value="">Semua kelas</option>
<?php foreach ($daftarKelas as $item): ?>
            <option value="<?= esc($item) ?>"<?= $item === $kelas ? ' selected' : '' ?>><?= esc($item) ?></option>
<?php endforeach ?>
        </select>
    </div>
    <div class="col-6 col-md">
        <label for="cari" class="form-label">Cari nama atau NIS</label>
        <input type="search" class="form-control" id="cari" name="cari" value="<?= esc($cari) ?>">
    </div>
    <div class="col-12 col-md-auto d-flex gap-2">
<?php if ($status !== ''): ?>
        <input type="hidden" name="status" value="<?= esc($status) ?>">
<?php endif ?>
        <button type="submit" class="btn btn-primary">Tampilkan</button>
<?php if ($tanggal !== $hariIni): ?>
        <a class="btn btn-outline-primary" href="<?= esc($tautan(['tanggal' => $hariIni])) ?>">Hari ini</a>
<?php endif ?>
    </div>
</form>

<h2 class="h5 mb-2"><?= esc(format_date($tanggal, 'full')) ?><?= $kelas === '' ? '' : ' · Kelas ' . esc($kelas) ?></h2>
<?php if ($rekap['libur'] !== null): ?>
<div class="alert alert-secondary">Libur: <?= esc($rekap['libur']) ?>. Tidak ada siswa yang otomatis Alpa.</div>
<?php endif ?>

<nav class="d-flex flex-wrap gap-2 mb-3" aria-label="Saring menurut status">
<?php foreach ($saringan as $kode => [$teks, $jumlah, $warnaBadge]): ?>
    <a class="btn btn-sm <?= $status === $kode ? 'btn-secondary' : 'btn-light border' ?>" href="<?= esc($tautan(['status' => $kode])) ?>"<?= $status === $kode ? ' aria-current="true"' : '' ?>><?= $teks ?> <span class="badge text-bg-<?= $warnaBadge ?>"><?= format_number($jumlah) ?></span></a>
<?php endforeach ?>
</nav>

<?php if ($rekap['baris'] === []): ?>
<p class="text-body-secondary">Belum ada siswa. Tambahkan siswa di menu Siswa.</p>
<?php elseif ($baris === []): ?>
<p class="text-body-secondary">Tidak ada siswa yang cocok. <a href="<?= esc($tautan(['status' => '', 'cari' => ''])) ?>">Tampilkan semua siswa</a>.</p>
<?php else: ?>
<?php if ($disaring): ?>
<p class="text-body-secondary mb-2">Menampilkan <?= format_number(count($baris)) ?> dari <?= format_number($total) ?> siswa. <a href="<?= esc($tautan(['status' => '', 'cari' => ''])) ?>">Tampilkan semua</a></p>
<?php endif ?>
<div class="table-responsive">
<table class="table table-hover align-middle bg-body">
    <thead>
        <tr>
            <th class="d-none d-md-table-cell">No</th>
            <th class="d-none d-md-table-cell">NIS</th>
            <th>Nama</th>
            <th class="d-none d-md-table-cell">Kelas</th>
            <th>Status</th>
            <th class="d-none d-md-table-cell">Jam scan</th>
            <th class="d-none d-lg-table-cell">Keterangan</th>
            <th><span class="visually-hidden">Ubah</span></th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($baris as $i => $siswa): ?>
<?php $kode = $siswa['status'] ?? 'belum'; ?>
        <tr id="siswa-<?= $siswa['id'] ?>">
            <td class="d-none d-md-table-cell"><?= format_number($i + 1) ?></td>
            <td class="d-none d-md-table-cell"><?= esc($siswa['nis']) ?></td>
            <td>
                <?= esc($siswa['nama']) ?>
                <div class="small text-body-secondary d-md-none">NIS <?= esc($siswa['nis']) ?> · Kelas <?= esc($siswa['kelas']) ?></div>
            </td>
            <td class="d-none d-md-table-cell"><?= esc($siswa['kelas']) ?></td>
            <td>
                <span class="badge text-bg-<?= $warna[$kode] ?>"><?= $label[$kode] ?></span>
<?php if ($siswa['jam_scan'] !== null): ?>
                <div class="small text-body-secondary d-md-none">Pukul <?= esc(format_time($siswa['jam_scan'])) ?></div>
<?php endif ?>
<?php if ($siswa['keterangan'] !== null): ?>
                <div class="small text-body-secondary d-lg-none"><?= esc($siswa['keterangan']) ?></div>
<?php endif ?>
            </td>
            <td class="d-none d-md-table-cell"><?= esc(format_time($siswa['jam_scan'])) ?></td>
            <td class="d-none d-lg-table-cell"><?= esc($siswa['keterangan'] ?? '') ?></td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#ubah-status"
                    data-siswa-id="<?= $siswa['id'] ?>" data-nama="<?= esc($siswa['nama']) ?>" data-status="<?= esc($siswa['status'] ?? '') ?>" data-keterangan="<?= esc($siswa['keterangan'] ?? '') ?>">Ubah</button>
            </td>
        </tr>
<?php endforeach ?>
    </tbody>
</table>
</div>

<div class="modal fade" id="ubah-status" tabindex="-1" aria-labelledby="ubah-status-judul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= url_to('rekap.ubah') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="siswa_id" value="">
            <input type="hidden" name="tanggal" value="<?= esc($tanggal) ?>">
            <input type="hidden" name="kelas" value="<?= esc($kelas) ?>">
            <input type="hidden" name="filter_status" value="<?= esc($status) ?>">
            <input type="hidden" name="cari" value="<?= esc($cari) ?>">
            <div class="modal-header">
                <h2 class="modal-title h5" id="ubah-status-judul">Ubah status <span id="ubah-status-nama"></span></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="text-body-secondary mb-2"><?= esc(format_date($tanggal, 'full')) ?></p>
                <fieldset class="row row-cols-2 row-cols-sm-3 g-2 mb-3">
                    <legend class="visually-hidden">Status</legend>
<?php foreach (Presensi::STATUS as $kode => $teks): ?>
                    <div class="col">
                        <input type="radio" class="btn-check" name="status" id="status-<?= $kode ?>" value="<?= $kode ?>" autocomplete="off">
                        <label class="btn btn-outline-secondary w-100" for="status-<?= $kode ?>"><span class="badge text-bg-<?= $warna[$kode] ?> me-1">&nbsp;</span><?= $teks ?></label>
                    </div>
<?php endforeach ?>
                    <div class="col-12">
                        <input type="radio" class="btn-check" name="status" id="status-kosong" value="" autocomplete="off">
                        <label class="btn btn-outline-secondary w-100" for="status-kosong">Kosongkan (hapus catatan)</label>
                    </div>
                </fieldset>
                <label for="keterangan" class="form-label">Keterangan <span class="text-body-secondary">(boleh kosong)</span></label>
                <input type="text" class="form-control" id="keterangan" name="keterangan" maxlength="255">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
<?php endif ?>
<?= $this->endSection() ?>
