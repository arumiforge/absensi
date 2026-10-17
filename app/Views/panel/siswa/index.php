<?= $this->extend('layout/panel') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="h3 mb-0">Siswa</h1>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary" href="<?= url_to('siswa.tambah') ?>">Tambah siswa</a>
        <a class="btn btn-outline-primary" href="<?= url_to('siswa.import') ?>">Import Excel</a>
        <a class="btn btn-outline-secondary" href="<?= url_to('siswa.kartu') . ($kelas === '' ? '' : '?' . esc(http_build_query(['kelas' => $kelas]))) ?>">Cetak kartu QR<?= $kelas === '' ? '' : ' kelas ' . esc($kelas) ?></a>
    </div>
</div>

<form method="get" action="<?= url_to('siswa') ?>" class="row g-2 align-items-end mb-3">
    <div class="col-sm">
        <label for="cari" class="form-label">Cari nama atau NIS</label>
        <input type="search" class="form-control" id="cari" name="cari" value="<?= esc($cari) ?>">
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
    <div class="col-sm-auto"><button type="submit" class="btn btn-primary">Cari</button></div>
</form>

<?php if ($siswa === []): ?>
<p class="text-body-secondary">Belum ada siswa yang cocok.</p>
<?php else: ?>
<p class="text-body-secondary"><?= format_number($pager->getTotal()) ?> siswa</p>
<div class="table-responsive">
<table class="table table-hover align-middle bg-body">
    <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th></th></tr></thead>
    <tbody>
<?php foreach ($siswa as $item): ?>
        <tr>
            <td><?= esc($item['nis']) ?></td>
            <td><?= esc($item['nama']) ?></td>
            <td><?= esc($item['kelas']) ?></td>
            <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="<?= url_to('siswa.ubah', $item['id']) ?>">Ubah</a>
                <form method="post" action="<?= url_to('siswa.hapus', $item['id']) ?>" class="d-inline" data-konfirmasi="Hapus <?= esc($item['nama']) ?>? Semua data presensinya ikut terhapus.">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                </form>
            </td>
        </tr>
<?php endforeach ?>
    </tbody>
</table>
</div>
<?= $pager->links('default', 'bootstrap') ?>
<?php endif ?>
<?= $this->endSection() ?>
