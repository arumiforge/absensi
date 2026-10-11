<?php
/**
 * Upload or replace one student's photo (docs/09 HAL-MD-09, docs/04
 * FS-MD-07). The chosen file is previewed before saving by
 * aset/js/pratinjau-foto.js; without JavaScript the form still works.
 *
 * @var array<string, mixed> $siswa
 * @var string|null          $urlFoto Current photo, or null when the student has none
 */
$galat = validation_errors();
$ikon  = static fn (string $name): string => view('komponen/ikon', ['name' => $name], ['saveData' => false]);
$id    = (int) $siswa['id'];
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => 'Foto siswa', 'context' => $siswa['nama'] . ' · NISN ' . $siswa['nisn']], ['saveData' => false]) ?>

<?= view('panel/penempatan/_galat', ['galat' => $galat, 'labels' => ['foto' => 'File foto']], ['saveData' => false]) ?>

<div class="row"><div class="col-lg-8 col-xl-6">
<form method="post" action="<?= esc(url_to('panel.siswa_foto.ganti', $id), 'attr') ?>" enctype="multipart/form-data" class="card card-body">
    <?= csrf_field() ?>
    <input type="hidden" name="_method" value="PUT">
    <div class="d-flex flex-wrap gap-4 mb-3">
        <figure class="mb-0">
            <?= view('komponen/foto_siswa', ['name' => $siswa['nama'], 'src' => $urlFoto, 'size' => 'profil', 'decorative' => false], ['saveData' => false]) ?>
            <figcaption class="form-text"><?= $urlFoto === null ? 'Belum ada foto.' : 'Foto sekarang' ?></figcaption>
        </figure>
        <figure class="mb-0" id="pratinjau-foto" hidden>
            <img class="foto-siswa foto-siswa-profil" alt="Pratinjau foto baru <?= esc($siswa['nama'], 'attr') ?>" width="150" height="200">
            <figcaption class="form-text">Foto baru, belum disimpan</figcaption>
        </figure>
    </div>
    <div class="mb-4">
        <label class="form-label" for="foto"><?= $urlFoto === null ? 'Pilih foto' : 'Pilih foto baru' ?></label>
        <input class="form-control<?= isset($galat['foto']) ? ' is-invalid" aria-invalid="true' : '' ?>" type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" required data-pratinjau-foto="pratinjau-foto" aria-describedby="<?= isset($galat['foto']) ? 'foto-galat ' : '' ?>foto-bantuan">
<?php if (isset($galat['foto'])): ?>
        <div class="invalid-feedback" id="foto-galat"><?= esc($galat['foto']) ?></div>
<?php endif ?>
        <div class="form-text" id="foto-bantuan">JPG, PNG, atau WebP, paling besar 10 MB. Foto diperkecil otomatis tanpa dipotong. Pakai foto tegak dengan wajah terlihat jelas.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit"><?= $ikon('check') ?> Simpan foto</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.siswa.lihat', $id), 'attr') ?>">Batal</a>
    </div>
</form>
</div></div>
<script type="module" src="<?= esc(base_url('aset/js/pratinjau-foto.js') . '?v=' . config('Spensada')->versi, 'attr') ?>"></script>
<?= $this->endSection() ?>
