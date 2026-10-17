<?= $this->extend('layout/panel') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3">Ganti password</h1>
<form method="post" action="<?= url_to('password.ganti') ?>" class="card card-body col-lg-5">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label for="password_lama" class="form-label">Password lama</label>
        <input type="password" class="form-control" id="password_lama" name="password_lama" autocomplete="current-password" required>
    </div>
    <div class="mb-3">
        <label for="password_baru" class="form-label">Password baru</label>
        <input type="password" class="form-control" id="password_baru" name="password_baru" minlength="8" autocomplete="new-password" required>
        <div class="form-text">Paling sedikit 8 karakter.</div>
    </div>
    <div class="mb-3">
        <label for="password_ulang" class="form-label">Ulangi password baru</label>
        <input type="password" class="form-control" id="password_ulang" name="password_ulang" minlength="8" autocomplete="new-password" required>
    </div>
    <div><button type="submit" class="btn btn-primary">Simpan</button></div>
</form>
<?= $this->endSection() ?>
