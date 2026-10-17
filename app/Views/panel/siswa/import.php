<?= $this->extend('layout/panel') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3">Import siswa</h1>
<?php if ($galat !== []): ?>
<div class="alert alert-danger" role="alert">
    <p class="mb-2">Belum ada data yang disimpan. Perbaiki baris berikut di file, lalu import lagi.</p>
    <ul class="mb-0">
<?php foreach (array_slice($galat, 0, 50) as $pesan): ?>
        <li><?= esc($pesan) ?></li>
<?php endforeach ?>
<?php if (count($galat) > 50): ?>
        <li>Dan <?= format_number(count($galat) - 50) ?> baris lainnya.</li>
<?php endif ?>
    </ul>
</div>
<?php endif ?>
<div class="card card-body col-lg-8">
    <p>Siapkan file Excel dengan 3 kolom: <strong>A NIS</strong>, <strong>B Nama</strong>, <strong>C Kelas</strong>. Baris pertama boleh berisi judul kolom.</p>
    <p>NIS yang sudah ada akan diperbarui nama dan kelasnya. Bila ada 1 baris yang salah, seluruh file tidak disimpan.</p>
    <form method="post" action="<?= url_to('siswa.import.proses') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="berkas" class="form-label">File (.xlsx, .xls, atau .csv)</label>
            <input type="file" class="form-control" id="berkas" name="berkas" accept=".xlsx,.xls,.csv" required>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Import</button>
            <a class="btn btn-outline-secondary" href="<?= url_to('siswa') ?>">Batal</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
