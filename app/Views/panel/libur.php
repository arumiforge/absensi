<?= $this->extend('layout/panel') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-1">Libur</h1>
<p class="text-body-secondary">Hari Minggu selalu libur. Tandai tanggal lain yang libur agar siswa tidak otomatis tercatat Alpa.</p>
<form method="post" action="<?= url_to('libur.simpan') ?>" class="row g-2 align-items-end mb-4">
    <?= csrf_field() ?>
    <div class="col-sm-auto">
        <label for="tanggal" class="form-label">Tanggal</label>
        <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= esc(old('tanggal')) ?>" required>
    </div>
    <div class="col-sm">
        <label for="keterangan" class="form-label">Keterangan</label>
        <input type="text" class="form-control" id="keterangan" name="keterangan" maxlength="100" placeholder="Contoh: Libur Maulid Nabi" value="<?= esc(old('keterangan')) ?>" required>
    </div>
    <div class="col-sm-auto"><button type="submit" class="btn btn-primary">Simpan</button></div>
</form>
<?php if ($libur === []): ?>
<p class="text-body-secondary">Belum ada tanggal libur.</p>
<?php else: ?>
<table class="table align-middle bg-body">
    <thead><tr><th>Tanggal</th><th>Keterangan</th><th></th></tr></thead>
    <tbody>
<?php foreach ($libur as $item): ?>
        <tr>
            <td><?= esc(format_date($item['tanggal'], 'full')) ?></td>
            <td><?= esc($item['keterangan']) ?></td>
            <td class="text-end">
                <form method="post" action="<?= url_to('libur.hapus', $item['tanggal']) ?>" data-konfirmasi="Hapus libur ini?">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                </form>
            </td>
        </tr>
<?php endforeach ?>
    </tbody>
</table>
<?php endif ?>
<?= $this->endSection() ?>
