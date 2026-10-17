<?= $this->extend('layout/panel') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($title) ?></h1>
<form method="post" action="<?= $siswa === null ? url_to('siswa.simpan') : url_to('siswa.perbarui', $siswa['id']) ?>" class="card card-body col-lg-6">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label for="nis" class="form-label">NIS</label>
        <input type="text" class="form-control" id="nis" name="nis" maxlength="20" value="<?= esc(old('nis', $siswa['nis'] ?? '')) ?>" required>
        <div class="form-text">Isi kode QR di kartu siswa. Bila NIS diubah, kartu lama tidak berlaku lagi.</div>
    </div>
    <div class="mb-3">
        <label for="nama" class="form-label">Nama</label>
        <input type="text" class="form-control" id="nama" name="nama" maxlength="100" value="<?= esc(old('nama', $siswa['nama'] ?? '')) ?>" required>
    </div>
    <div class="mb-3">
        <label for="kelas" class="form-label">Kelas</label>
        <input type="text" class="form-control" id="kelas" name="kelas" maxlength="20" placeholder="Contoh: 7A" value="<?= esc(old('kelas', $siswa['kelas'] ?? '')) ?>" required>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a class="btn btn-outline-secondary" href="<?= url_to('siswa') ?>">Batal</a>
    </div>
</form>
<?= $this->endSection() ?>
